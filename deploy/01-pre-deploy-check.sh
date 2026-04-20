#!/usr/bin/env bash
# ============================================================
# Pre-deploy safety check
# ============================================================
# รันก่อน deploy เพื่อเช็ค environment production พร้อมหรือยัง
# Usage:  bash deploy/01-pre-deploy-check.sh
# ============================================================

set -u  # error if unset variable (ไม่ใช้ -e เพราะอยาก check หลายอย่างแม้บางอันจะ fail)

APP_DIR="${APP_DIR:-/var/www/vendr}"
PHP_BIN="${PHP_BIN:-php}"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() { echo -e "${GREEN}[OK]${NC}   $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; FAILURES=$((FAILURES+1)); }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; WARNINGS=$((WARNINGS+1)); }
info() { echo -e "       $1"; }

FAILURES=0
WARNINGS=0

echo "============================================================"
echo " PRE-DEPLOY SAFETY CHECK"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"
echo ""

# ─── 1) directory ────────────────────────────────────────────
if [ -d "$APP_DIR" ]; then
    pass "App directory exists: $APP_DIR"
    cd "$APP_DIR" || exit 1
else
    fail "App directory not found: $APP_DIR"
    echo "กรุณาตั้ง APP_DIR=/path/to/vendr แล้วรันใหม่"
    exit 1
fi

# ─── 2) git status ───────────────────────────────────────────
if git rev-parse --git-dir > /dev/null 2>&1; then
    pass "Git repository detected"
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    COMMIT=$(git rev-parse --short HEAD)
    info "Current branch:  $BRANCH"
    info "Current commit:  $COMMIT"

    # เช็ค uncommitted changes
    if [ -n "$(git status --porcelain)" ]; then
        warn "มี uncommitted changes บน production server — อาจทับกับ deploy"
        git status --short | head -20
    else
        pass "Working tree clean"
    fi
else
    fail "Not a git repository"
fi

# ─── 3) PHP & extensions ─────────────────────────────────────
if command -v "$PHP_BIN" > /dev/null 2>&1; then
    PHP_VERSION=$($PHP_BIN -r 'echo PHP_VERSION;')
    pass "PHP available: $PHP_VERSION"

    # check required extensions
    for ext in pdo_mysql mbstring openssl tokenizer xml ctype json bcmath; do
        if $PHP_BIN -m | grep -qi "^$ext$"; then
            pass "PHP extension: $ext"
        else
            fail "Missing PHP extension: $ext"
        fi
    done
else
    fail "PHP binary not found: $PHP_BIN"
fi

# ─── 4) composer ─────────────────────────────────────────────
if command -v composer > /dev/null 2>&1; then
    pass "Composer available"
else
    fail "Composer not found"
fi

# ─── 5) .env file ────────────────────────────────────────────
if [ -f "$APP_DIR/.env" ]; then
    pass ".env file exists"

    if grep -q "^APP_ENV=production" "$APP_DIR/.env"; then
        pass "APP_ENV=production"
    else
        warn "APP_ENV ไม่ใช่ production — ตรวจสอบว่าถูก server หรือไม่"
    fi

    if grep -q "^APP_DEBUG=false" "$APP_DIR/.env"; then
        pass "APP_DEBUG=false"
    else
        fail "APP_DEBUG ไม่ใช่ false — ต้องปิด debug บน production!"
    fi

    if grep -q "^APP_KEY=base64:" "$APP_DIR/.env"; then
        pass "APP_KEY set"
    else
        fail "APP_KEY ไม่ได้ตั้งค่า"
    fi
else
    fail ".env file not found"
fi

# ─── 6) database connection ──────────────────────────────────
if [ -f "$APP_DIR/artisan" ]; then
    DB_OK=$($PHP_BIN "$APP_DIR/artisan" tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';" 2>&1 | tail -1)
    if [[ "$DB_OK" == *"OK"* ]]; then
        pass "Database connection working"
    else
        fail "Database connection failed"
        info "$DB_OK"
    fi

    # check migration status
    PENDING=$($PHP_BIN "$APP_DIR/artisan" migrate:status 2>/dev/null | grep -c "Pending" || true)
    if [ "$PENDING" -gt 0 ]; then
        warn "มี pending migrations จำนวน $PENDING → script deploy จะ run migrate"
    else
        pass "No pending migrations"
    fi
else
    fail "artisan not found"
fi

# ─── 7) queue worker ─────────────────────────────────────────
if command -v supervisorctl > /dev/null 2>&1; then
    if supervisorctl status 2>/dev/null | grep -qi queue; then
        pass "Queue worker managed by supervisor"
        supervisorctl status | grep -i queue | sed 's/^/       /'
    else
        warn "ไม่พบ queue worker ใน supervisor"
        info "ถ้าใช้ queue driver = database/redis ต้องมี worker รันอยู่"
    fi
else
    warn "supervisorctl ไม่มี — อาจใช้ systemd หรือวิธีอื่น"
fi

# ─── 8) cron schedule ────────────────────────────────────────
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    pass "Laravel scheduler ติดตั้งใน crontab"
else
    warn "ไม่พบ 'schedule:run' ใน crontab → scheduled commands อาจไม่ทำงาน"
fi

# ─── 9) disk space ───────────────────────────────────────────
DISK_USAGE=$(df -h "$APP_DIR" | tail -1 | awk '{print $5}' | tr -d '%')
if [ "$DISK_USAGE" -lt 80 ]; then
    pass "Disk usage: ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -lt 90 ]; then
    warn "Disk usage สูง: ${DISK_USAGE}%"
else
    fail "Disk usage เต็ม: ${DISK_USAGE}% — clear logs ก่อน deploy"
fi

# ─── 10) storage permissions ─────────────────────────────────
if [ -w "$APP_DIR/storage" ] && [ -w "$APP_DIR/bootstrap/cache" ]; then
    pass "Storage directories writable"
else
    fail "Storage/bootstrap cache ไม่ writable — fix permission ก่อน deploy"
fi

# ─── 11) backup database (check tool) ───────────────────────
if command -v mysqldump > /dev/null 2>&1; then
    pass "mysqldump available (สำหรับ backup)"
else
    warn "mysqldump ไม่มี — ควรติดตั้งเพื่อ backup ก่อน deploy"
fi

# ─── summary ────────────────────────────────────────────────
echo ""
echo "============================================================"
echo " SUMMARY"
echo "============================================================"
echo " Failures: $FAILURES"
echo " Warnings: $WARNINGS"
echo ""

if [ "$FAILURES" -gt 0 ]; then
    echo -e "${RED}❌ Pre-deploy check FAILED — แก้ errors ข้างบนก่อน deploy${NC}"
    exit 1
elif [ "$WARNINGS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Pre-deploy check passed with warnings — review ก่อนตัดสินใจ${NC}"
    exit 0
else
    echo -e "${GREEN}✅ Pre-deploy check PASSED — พร้อม deploy${NC}"
    exit 0
fi
