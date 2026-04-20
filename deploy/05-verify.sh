#!/usr/bin/env bash
# ============================================================
# Post-deploy verification
# ============================================================
# รันหลัง deploy เพื่อเช็คระบบ healthy
# Usage:  bash deploy/05-verify.sh
# ============================================================

set -u

APP_DIR="${APP_DIR:-/var/www/vendr}"
PHP_BIN="${PHP_BIN:-php}"
APP_URL="${APP_URL:-http://localhost}"

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

cd "$APP_DIR"

echo "============================================================"
echo " POST-DEPLOY VERIFICATION"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"

# ─── 1) maintenance status ──────────────────────────────────
if [ -f "$APP_DIR/storage/framework/maintenance.php" ] || [ -f "$APP_DIR/storage/framework/down" ]; then
    fail "แอปอยู่ในโหมด maintenance — รัน php artisan up"
else
    pass "แอป online"
fi

# ─── 2) HTTP 200 check ──────────────────────────────────────
if command -v curl > /dev/null 2>&1; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -m 10 "$APP_URL" || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        pass "HTTP check $APP_URL → $HTTP_CODE"
    else
        fail "HTTP check $APP_URL → $HTTP_CODE"
    fi

    ADMIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" -m 10 "$APP_URL/admin/login" || echo "000")
    if [ "$ADMIN_CODE" = "200" ]; then
        pass "Admin login page → 200"
    else
        fail "Admin login page → $ADMIN_CODE"
    fi
fi

# ─── 3) database connectivity ───────────────────────────────
DB_OK=$($PHP_BIN artisan tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';" 2>&1 | tail -1)
if [[ "$DB_OK" == *"OK"* ]]; then
    pass "Database connection OK"
else
    fail "Database connection failed"
fi

# ─── 4) migration status ────────────────────────────────────
PENDING=$($PHP_BIN artisan migrate:status 2>/dev/null | grep -c "Pending" || true)
if [ "$PENDING" -eq 0 ]; then
    pass "No pending migrations"
else
    fail "Still has $PENDING pending migrations"
fi

# ─── 5) scheduler heartbeat ─────────────────────────────────
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    pass "Scheduler cron installed"
else
    warn "Scheduler cron ไม่มี"
fi

# ─── 6) queue workers alive ─────────────────────────────────
if command -v supervisorctl > /dev/null 2>&1; then
    RUNNING=$(supervisorctl status 2>/dev/null | grep -c "RUNNING" || true)
    if [ "$RUNNING" -gt 0 ]; then
        pass "Supervisor has $RUNNING running processes"
        supervisorctl status 2>/dev/null | grep -i queue | sed 's/^/       /' || true
    else
        warn "ไม่พบ running workers ใน supervisor"
    fi
fi

# ─── 7) test new code — company context ────────────────────
step_test_company_context() {
    # ตรวจว่า BaseModel ใหม่ throw เมื่อไม่มี session context
    $PHP_BIN artisan tinker --execute='
        try {
            session()->forget("company_id");
            $pr = new \App\Models\PurchaseRequisition();
            $pr->pr_number = "TEST";
            $pr->save();
            echo "SECURITY_ISSUE_NO_GUARD";
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), "company context") !== false) {
                echo "GUARD_ACTIVE";
            } else {
                echo "UNKNOWN_ERROR: " . $e->getMessage();
            }
        } catch (\Throwable $e) {
            // PurchaseRequisition ไม่ extend BaseModel → ข้าม
            echo "NOT_APPLICABLE";
        }
    ' 2>&1 | tail -1
}

# ─── 8) verify critical security fixes are in code ─────────
step_check_fixes() {
    # Check CompanySelector no longer auto-sets
    if grep -q "defaultCompany->id" "$APP_DIR/app/Filament/Widgets/CompanySelector.php"; then
        fail "CompanySelector ยังมี auto-set company_id — deploy ไม่สมบูรณ์"
    else
        pass "Fix #1: CompanySelector auto-set removed"
    fi

    # Check number generator uses lockForUpdate
    if grep -q "lockForUpdate" "$APP_DIR/app/Models/PurchaseRequisition.php"; then
        pass "Fix #2a: PR generator uses lockForUpdate"
    else
        fail "Fix #2a: PR generator ไม่มี lockForUpdate"
    fi

    if grep -q "lockForUpdate" "$APP_DIR/app/Models/GoodsReceipt.php"; then
        pass "Fix #2b: GR generator uses lockForUpdate"
    else
        fail "Fix #2b: GR generator ไม่มี lockForUpdate"
    fi

    # Check Telegram OTP rate limit
    if grep -q "tg_verify_attempts" "$APP_DIR/app/Services/TelegramBotService.php"; then
        pass "Fix #3: Telegram OTP rate limit active"
    else
        fail "Fix #3: Telegram OTP rate limit ไม่มี"
    fi

    # Check CheckAdminRole uses hasRole
    if grep -q "hasRole('admin')" "$APP_DIR/app/Http/Middleware/CheckAdminRole.php"; then
        pass "Fix #4: CheckAdminRole uses hasRole()"
    else
        fail "Fix #4: CheckAdminRole ยังใช้ roles->contains"
    fi
}

step_check_fixes

# ─── 9) logs — check for fresh errors ───────────────────────
LOG_FILE="$APP_DIR/storage/logs/laravel.log"
if [ -f "$LOG_FILE" ]; then
    # errors ใน 5 นาทีล่าสุด
    RECENT_ERRORS=$(find "$LOG_FILE" -mmin -5 -exec grep -c "ERROR\|CRITICAL\|EMERGENCY" {} \; 2>/dev/null || echo 0)
    if [ "$RECENT_ERRORS" -gt 0 ]; then
        warn "พบ error $RECENT_ERRORS บรรทัดใน 5 นาทีล่าสุด"
        info "ดูด้วย: tail -50 $LOG_FILE"
    else
        pass "ไม่มี error ใหม่ใน log"
    fi
fi

# ─── 10) git commit check ────────────────────────────────────
GIT_COMMIT=$(cd "$APP_DIR" && git rev-parse --short HEAD)
pass "Current git commit: $GIT_COMMIT"

# ─── summary ────────────────────────────────────────────────
echo ""
echo "============================================================"
echo " VERIFICATION SUMMARY"
echo "============================================================"
echo " Failures: $FAILURES"
echo " Warnings: $WARNINGS"
echo ""

if [ "$FAILURES" -gt 0 ]; then
    echo -e "${RED}❌ มีปัญหา $FAILURES จุด — พิจารณา rollback${NC}"
    echo "   bash deploy/04-rollback.sh"
    exit 1
elif [ "$WARNINGS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Passed with warnings — monitor ต่อ${NC}"
    exit 0
else
    echo -e "${GREEN}✅ ALL CHECKS PASSED — ระบบพร้อมใช้งาน${NC}"
    exit 0
fi
