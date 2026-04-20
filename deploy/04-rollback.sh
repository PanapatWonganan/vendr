#!/usr/bin/env bash
# ============================================================
# Rollback script — กู้คืน code + database จาก backup ล่าสุด
# ============================================================
# Usage:
#   bash deploy/04-rollback.sh              # ใช้ backup ล่าสุด
#   bash deploy/04-rollback.sh 20260420_143000   # ระบุ timestamp
#
# ⚠️ WARNING: จะ overwrite database ปัจจุบัน!
# ============================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/vendr}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/vendr}"
PHP_BIN="${PHP_BIN:-php}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

step() { echo -e "\n${BLUE}▶ $1${NC}"; }
ok()   { echo -e "  ${GREEN}✓${NC} $1"; }
err()  { echo -e "  ${RED}✗${NC} $1"; }

# ─── resolve backup timestamp ────────────────────────────────
if [ $# -ge 1 ]; then
    TIMESTAMP="$1"
else
    if [ -f "$BACKUP_DIR/.last_backup" ]; then
        TIMESTAMP=$(cat "$BACKUP_DIR/.last_backup")
    else
        echo -e "${RED}❌ ไม่พบ .last_backup — ต้องระบุ timestamp${NC}"
        echo ""
        echo "Backups ที่มี:"
        ls -1 "$BACKUP_DIR" 2>/dev/null | grep -E "^db_.*\.sql\.gz$" | sed 's/^/  /'
        exit 1
    fi
fi

echo "============================================================"
echo -e "${YELLOW} ⚠️  ROLLBACK${NC}"
echo "============================================================"
echo " Target app:      $APP_DIR"
echo " Backup timestamp: $TIMESTAMP"
echo ""

# ─── locate backup files ─────────────────────────────────────
DB_BACKUP=$(ls "$BACKUP_DIR"/db_*_"$TIMESTAMP".sql.gz 2>/dev/null | head -1 || true)
STORAGE_BACKUP="$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz"
COMMIT_FILE="$BACKUP_DIR/git_commit_${TIMESTAMP}.txt"

if [ -z "$DB_BACKUP" ] || [ ! -f "$DB_BACKUP" ]; then
    err "ไม่พบ database backup สำหรับ timestamp $TIMESTAMP"
    exit 1
fi
ok "DB backup:      $DB_BACKUP"

if [ -f "$STORAGE_BACKUP" ]; then
    ok "Storage backup: $STORAGE_BACKUP"
else
    echo -e "  ${YELLOW}!${NC} ไม่พบ storage backup (จะข้าม)"
fi

if [ -f "$COMMIT_FILE" ]; then
    ROLLBACK_COMMIT=$(cat "$COMMIT_FILE")
    ok "Git commit: $ROLLBACK_COMMIT"
else
    err "ไม่พบ commit hash file — จะไม่ revert code"
    ROLLBACK_COMMIT=""
fi

echo ""
read -r -p "ยืนยัน ROLLBACK? (พิมพ์ 'ROLLBACK' เพื่อยืนยัน): " CONFIRM
if [ "$CONFIRM" != "ROLLBACK" ]; then
    echo "ยกเลิก"
    exit 0
fi

# ─── 1) maintenance mode ─────────────────────────────────────
step "Maintenance mode ON"
cd "$APP_DIR"
$PHP_BIN artisan down --retry=60 --secret="rollback-$(date +%s)" || true
ok "Maintenance ON"

# ─── 2) stop workers ─────────────────────────────────────────
step "Stop queue workers"
if command -v supervisorctl > /dev/null 2>&1; then
    supervisorctl stop "vendr-queue:*" 2>/dev/null || supervisorctl stop all 2>/dev/null || true
    ok "Workers stopped"
fi

# ─── 3) revert code ──────────────────────────────────────────
if [ -n "$ROLLBACK_COMMIT" ]; then
    step "Reverting code to commit $ROLLBACK_COMMIT"
    git fetch --all
    git reset --hard "$ROLLBACK_COMMIT"
    ok "Code reverted"

    # reinstall composer for old code
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    ok "Composer re-installed"
else
    echo -e "  ${YELLOW}!${NC} Skip code revert"
fi

# ─── 4) restore database ─────────────────────────────────────
step "Restore database from $DB_BACKUP"
DB_HOST=$(grep "^DB_HOST=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PORT=$(grep "^DB_PORT=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_DATABASE=$(grep "^DB_DATABASE=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep "^DB_USERNAME=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep "^DB_PASSWORD=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

# snapshot ของ state ปัจจุบันก่อน restore (safety net)
PRE_ROLLBACK_DUMP="$BACKUP_DIR/pre_rollback_$(date +%Y%m%d_%H%M%S).sql.gz"
echo "  → Snapshot ก่อน rollback → $PRE_ROLLBACK_DUMP"
mysqldump \
    --host="$DB_HOST" --port="$DB_PORT" \
    --user="$DB_USERNAME" --password="$DB_PASSWORD" \
    --single-transaction --quick --lock-tables=false \
    --no-tablespaces \
    "$DB_DATABASE" | gzip > "$PRE_ROLLBACK_DUMP"

echo "  → Restoring..."
gunzip -c "$DB_BACKUP" | mysql \
    --host="$DB_HOST" --port="$DB_PORT" \
    --user="$DB_USERNAME" --password="$DB_PASSWORD" \
    "$DB_DATABASE"
ok "Database restored"

# ─── 5) restore storage (optional) ───────────────────────────
if [ -f "$STORAGE_BACKUP" ]; then
    step "Restore storage directory"
    read -r -p "  restore storage ด้วย? (y/N): " RESTORE_STORAGE
    if [ "$RESTORE_STORAGE" = "y" ] || [ "$RESTORE_STORAGE" = "Y" ]; then
        tar xzf "$STORAGE_BACKUP" -C "$APP_DIR"
        ok "Storage restored"
    else
        echo "  skip storage restore"
    fi
fi

# ─── 6) rebuild caches ───────────────────────────────────────
step "Clear & rebuild caches"
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
ok "Caches rebuilt"

# ─── 7) restart services ─────────────────────────────────────
step "Restart services"
$PHP_BIN artisan queue:restart

if command -v supervisorctl > /dev/null 2>&1; then
    supervisorctl start "vendr-queue:*" 2>/dev/null || supervisorctl start all 2>/dev/null || true
fi

if command -v systemctl > /dev/null 2>&1; then
    PHP_FPM_SVC=$(systemctl list-units --type=service 2>/dev/null | grep -oE 'php[0-9.]*-fpm' | head -1)
    [ -n "$PHP_FPM_SVC" ] && systemctl reload "$PHP_FPM_SVC" 2>/dev/null || true
fi
ok "Services restarted"

# ─── 8) exit maintenance ─────────────────────────────────────
step "Maintenance mode OFF"
$PHP_BIN artisan up
ok "System ONLINE"

echo ""
echo "============================================================"
echo -e "${GREEN}✅ ROLLBACK COMPLETED${NC}"
echo "============================================================"
echo " Pre-rollback snapshot saved: $PRE_ROLLBACK_DUMP"
echo " เช็คระบบด้วย: bash deploy/05-verify.sh"
echo "============================================================"
