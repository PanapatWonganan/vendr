#!/usr/bin/env bash
# ============================================================
# Main deploy script — Critical security fixes
# ============================================================
# Usage:  bash deploy/03-deploy.sh
#
# Pre-requisites:
#   - ได้รัน bash deploy/01-pre-deploy-check.sh แล้ว ไม่มี FAIL
#   - ได้รัน bash deploy/02-backup.sh แล้ว
#   - Branch ตั้งไว้ถูก (main)
# ============================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/vendr}"
PHP_BIN="${PHP_BIN:-php}"
BRANCH="${DEPLOY_BRANCH:-main}"
WWW_USER="${WWW_USER:-www-data}"
WWW_GROUP="${WWW_GROUP:-www-data}"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

step() { echo -e "\n${BLUE}▶ $1${NC}"; }
ok()   { echo -e "  ${GREEN}✓${NC} $1"; }
err()  { echo -e "  ${RED}✗${NC} $1"; }
warn() { echo -e "  ${YELLOW}!${NC} $1"; }

trap 'echo -e "\n${RED}❌ DEPLOY FAILED${NC} — บรรทัด $LINENO\nสามารถ rollback ด้วย: bash deploy/04-rollback.sh"; exit 1' ERR

START_TIME=$(date +%s)

echo "============================================================"
echo " DEPLOY CRITICAL SECURITY FIXES"
echo " Target: $APP_DIR"
echo " Branch: $BRANCH"
echo " Time:   $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"
echo ""
read -r -p "ยืนยัน deploy? (พิมพ์ 'DEPLOY' เพื่อยืนยัน): " CONFIRM
if [ "$CONFIRM" != "DEPLOY" ]; then
    echo "ยกเลิก deploy"
    exit 0
fi

cd "$APP_DIR"

# ─── 1) Maintenance mode ────────────────────────────────────
step "1/10 เปิด Maintenance mode"
$PHP_BIN artisan down \
    --render="errors::503" \
    --retry=60 \
    --secret="deploy-$(date +%s)" || warn "down failed (อาจอยู่ใน maintenance อยู่แล้ว)"
ok "Maintenance mode ON"

# ─── 2) Stop queue workers ──────────────────────────────────
step "2/10 หยุด queue workers"
if command -v supervisorctl > /dev/null 2>&1; then
    supervisorctl stop "vendr-queue:*" 2>/dev/null || \
    supervisorctl stop all 2>/dev/null || \
    warn "supervisorctl stop failed — อาจต้องหยุด worker เอง"
    ok "Queue workers stopped"
else
    warn "ไม่พบ supervisorctl — ถ้ามี worker รันเองต้องหยุดก่อน"
fi

# ─── 3) Git pull ────────────────────────────────────────────
step "3/10 Pull code ใหม่"
git fetch --all --prune
OLD_COMMIT=$(git rev-parse HEAD)
ok "Current commit: $OLD_COMMIT"

git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

NEW_COMMIT=$(git rev-parse HEAD)
ok "New commit: $NEW_COMMIT"

if [ "$OLD_COMMIT" = "$NEW_COMMIT" ]; then
    warn "Commit ไม่เปลี่ยน — ไม่มีอะไร deploy ใหม่"
fi

# แสดงไฟล์ที่เปลี่ยน
echo ""
echo "  Files changed:"
git diff --name-only "$OLD_COMMIT" "$NEW_COMMIT" | head -30 | sed 's/^/    /'

# ─── 4) Composer install ────────────────────────────────────
step "4/10 Install composer dependencies"
composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress
ok "Composer deps installed"

# ─── 5) NPM build (optional) ────────────────────────────────
step "5/10 Build frontend assets"
if [ -f "package.json" ]; then
    if command -v npm > /dev/null 2>&1; then
        npm ci --silent
        npm run build
        ok "Frontend built"
    else
        warn "npm ไม่มี — ข้าม"
    fi
else
    warn "ไม่มี package.json — ข้าม"
fi

# ─── 6) Run migrations ──────────────────────────────────────
step "6/10 Run database migrations"
# NOTE: --force บังคับให้ run บน production (เพราะไม่มี --pretend)
# ⚠️ ห้ามใช้ migrate:fresh / migrate:refresh เด็ดขาด
$PHP_BIN artisan migrate --force --step
ok "Migrations applied"

# ─── 7) Clear + cache config ────────────────────────────────
step "7/10 Clear & rebuild caches"
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache 2>/dev/null || true
ok "Caches rebuilt"

# Filament-specific caches (ถ้ามี)
$PHP_BIN artisan filament:optimize 2>/dev/null || warn "filament:optimize ข้าม"
$PHP_BIN artisan icons:cache 2>/dev/null || true

# ─── 8) Storage link ────────────────────────────────────────
step "8/10 Ensure storage symlink"
if [ ! -L "public/storage" ]; then
    $PHP_BIN artisan storage:link
    ok "Storage link created"
else
    ok "Storage link already exists"
fi

# ─── 9) Fix permissions ─────────────────────────────────────
step "9/10 Fix file permissions"
chown -R "$WWW_USER:$WWW_GROUP" storage bootstrap/cache 2>/dev/null || \
    warn "chown failed — ต้อง sudo หรือตั้ง WWW_USER/WWW_GROUP"
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || warn "chmod failed"
ok "Permissions set"

# ─── 10) Restart services ───────────────────────────────────
step "10/10 Restart services"

# Restart queue workers to pick up new code
$PHP_BIN artisan queue:restart
ok "Signaled queue workers to restart"

# Start queue workers again (supervisor)
if command -v supervisorctl > /dev/null 2>&1; then
    supervisorctl start "vendr-queue:*" 2>/dev/null || \
    supervisorctl start all 2>/dev/null || \
    warn "start workers failed"
    sleep 2
    supervisorctl status 2>/dev/null | grep -i queue | sed 's/^/    /' || true
fi

# Reload PHP-FPM (ถ้ามี opcache ต้อง reload)
if command -v systemctl > /dev/null 2>&1; then
    if systemctl list-units --type=service 2>/dev/null | grep -q "php.*fpm"; then
        PHP_FPM_SVC=$(systemctl list-units --type=service | grep -oE 'php[0-9.]*-fpm' | head -1)
        if [ -n "$PHP_FPM_SVC" ]; then
            systemctl reload "$PHP_FPM_SVC" 2>/dev/null && ok "Reloaded $PHP_FPM_SVC" || warn "Reload $PHP_FPM_SVC failed"
        fi
    fi
fi

# ─── Exit maintenance mode ──────────────────────────────────
step "Exit maintenance mode"
$PHP_BIN artisan up
ok "Maintenance mode OFF — ระบบ online"

# ─── Summary ────────────────────────────────────────────────
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo "============================================================"
echo -e "${GREEN}✅ DEPLOY COMPLETED in ${DURATION}s${NC}"
echo "============================================================"
echo " Old commit: $OLD_COMMIT"
echo " New commit: $NEW_COMMIT"
echo ""
echo " ขั้นตอนถัดไป:"
echo "   1. รัน bash deploy/05-verify.sh เพื่อเช็คระบบ"
echo "   2. เปิด browser ทดสอบ login + flow หลัก"
echo "   3. Monitor logs: tail -f $APP_DIR/storage/logs/laravel.log"
echo "   4. Monitor queue: supervisorctl tail -f vendr-queue"
echo ""
echo " ถ้ามีปัญหา rollback ด้วย:"
echo "   bash deploy/04-rollback.sh"
echo "============================================================"
