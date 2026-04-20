#!/usr/bin/env bash
# ============================================================
# Backup database + storage/uploads ก่อน deploy
# ============================================================
# Usage:  bash deploy/02-backup.sh
# ============================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/vendr}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/vendr}"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

echo "============================================================"
echo " BACKUP SNAPSHOT"
echo " Timestamp: $TIMESTAMP"
echo "============================================================"

mkdir -p "$BACKUP_DIR"

# ─── 1) read .env for DB credentials ────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    echo "❌ .env file not found at $APP_DIR/.env"
    exit 1
fi

DB_HOST=$(grep "^DB_HOST=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PORT=$(grep "^DB_PORT=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_DATABASE=$(grep "^DB_DATABASE=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep "^DB_USERNAME=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep "^DB_PASSWORD=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

echo " Database: $DB_DATABASE @ $DB_HOST:$DB_PORT"
echo ""

# ─── 2) dump database ───────────────────────────────────────
DB_BACKUP_FILE="$BACKUP_DIR/db_${DB_DATABASE}_${TIMESTAMP}.sql.gz"
echo "→ Dumping database to $DB_BACKUP_FILE"

mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --no-tablespaces \
    "$DB_DATABASE" | gzip > "$DB_BACKUP_FILE"

DB_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
echo "  ✓ Database backed up ($DB_SIZE)"

# ─── 3) backup storage (uploads, PDFs, attachments) ─────────
STORAGE_BACKUP_FILE="$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz"
echo "→ Backing up storage directory"

tar czf "$STORAGE_BACKUP_FILE" \
    -C "$APP_DIR" \
    --exclude="storage/framework/cache/*" \
    --exclude="storage/framework/views/*" \
    --exclude="storage/framework/sessions/*" \
    --exclude="storage/logs/*.log" \
    storage/ 2>/dev/null || echo "  (some files skipped)"

STORAGE_SIZE=$(du -h "$STORAGE_BACKUP_FILE" | cut -f1)
echo "  ✓ Storage backed up ($STORAGE_SIZE)"

# ─── 4) save current git commit hash ────────────────────────
cd "$APP_DIR"
CURRENT_COMMIT=$(git rev-parse HEAD)
echo "$CURRENT_COMMIT" > "$BACKUP_DIR/git_commit_${TIMESTAMP}.txt"
echo "→ Saved git commit: $CURRENT_COMMIT"

# ─── 5) clean old backups (keep last 10) ────────────────────
echo "→ Cleaning old backups (keep 10 most recent)"
cd "$BACKUP_DIR"
ls -t db_*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm -f
ls -t storage_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f
ls -t git_commit_*.txt 2>/dev/null | tail -n +11 | xargs -r rm -f

echo ""
echo "============================================================"
echo "✅ Backup completed"
echo "  DB:      $DB_BACKUP_FILE"
echo "  Storage: $STORAGE_BACKUP_FILE"
echo "  Commit:  $BACKUP_DIR/git_commit_${TIMESTAMP}.txt"
echo ""
echo "จำไว้ timestamp นี้เผื่อต้อง rollback: $TIMESTAMP"
echo "============================================================"

# export สำหรับ script ถัดไป
echo "$TIMESTAMP" > "$BACKUP_DIR/.last_backup"
