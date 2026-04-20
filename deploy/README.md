# Vendr Production Deploy Scripts

Scripts สำหรับ deploy critical security fixes ขึ้น production บน Vultr

## ⚠️ Breaking Changes

Deploy ครั้งนี้มี breaking changes ต้องระวัง:

1. **BaseModel throws exception ถ้าไม่มี `session('company_id')`** — queue jobs/commands
   ที่ create model โดยไม่ set session อาจ fail
2. **PR/PO/GR/VA/TOR number generator throw exception ถ้าไม่มี company context**
3. **Admin routes เข้มขึ้น** — user ที่ role admin inactive/expired เข้าไม่ได้
4. **Company switch ต้องมี active role** — user ไม่มี role active → 403
5. **Telegram OTP rate limited** — 5 ครั้ง/10 นาที

## Prerequisites บน Production Server

- Linux (Ubuntu/Debian recommended)
- PHP 8.2+ พร้อม extensions: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath
- Composer 2.x
- MySQL 8.x (หรือ MariaDB)
- Node.js 18+ (ถ้าต้อง build assets)
- `mysqldump` สำหรับ backup
- supervisor สำหรับ queue worker (optional แต่แนะนำ)
- cron ติดตั้ง `schedule:run` ทุกนาที

## Environment Variables

ตั้งค่าก่อนรัน script (หรือ export ใน `.bashrc` ของ deploy user):

```bash
export APP_DIR=/var/www/vendr                    # path ของ app
export BACKUP_DIR=/var/backups/vendr             # path backup
export PHP_BIN=php                                # หรือ /usr/bin/php8.2
export DEPLOY_BRANCH=main                        # git branch
export WWW_USER=www-data                         # web server user
export WWW_GROUP=www-data
export APP_URL=https://vendr.yourdomain.com     # URL production
```

---

## Deploy Workflow (ลำดับคำสั่ง)

```bash
# ─── Step 0: SSH ขึ้น production server ─────────────────────
ssh user@your-vultr-ip
cd /var/www/vendr   # หรือ $APP_DIR

# ─── Step 1: Pull scripts ล่าสุด (ถ้ายังไม่มี) ──────────────
git fetch origin
git checkout origin/main -- deploy/
chmod +x deploy/*.sh

# ─── Step 2: Pre-deploy check ──────────────────────────────
bash deploy/01-pre-deploy-check.sh
# ถ้า FAIL → แก้ก่อน ห้าม deploy

# ─── Step 3: Backup ─────────────────────────────────────────
sudo bash deploy/02-backup.sh
# จะ backup DB + storage ไป /var/backups/vendr
# จำ timestamp ไว้เผื่อต้อง rollback

# ─── Step 4: Deploy ─────────────────────────────────────────
sudo bash deploy/03-deploy.sh
# จะถามยืนยัน — พิมพ์ 'DEPLOY' เพื่อ confirm
# ระบบจะเข้า maintenance mode ประมาณ 1-3 นาที

# ─── Step 5: Verify ─────────────────────────────────────────
bash deploy/05-verify.sh
# ถ้ามี FAIL → พิจารณา rollback
```

---

## Rollback (ถ้ามีปัญหา)

```bash
# ใช้ backup ล่าสุด
sudo bash deploy/04-rollback.sh

# หรือระบุ timestamp เอง
sudo bash deploy/04-rollback.sh 20260420_143000
```

Rollback จะ:
1. เปิด maintenance mode
2. หยุด queue workers
3. `git reset --hard` กลับไป commit เดิมก่อน deploy
4. Snapshot DB ปัจจุบัน (เผื่อต้องกู้อีกรอบ) → `pre_rollback_*.sql.gz`
5. Restore DB จาก backup
6. (optional) Restore storage
7. Rebuild caches + restart services
8. ปิด maintenance mode

---

## Manual Steps (ถ้า script มีปัญหา)

### Maintenance mode

```bash
php artisan down --retry=60
# ... deploy steps ...
php artisan up
```

### Manual backup DB

```bash
mysqldump -u$USER -p$PASSWORD $DATABASE \
    --single-transaction --quick --no-tablespaces \
    | gzip > /var/backups/vendr/manual_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Manual deploy

```bash
cd /var/www/vendr
git fetch --all
git reset --hard origin/main

composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

sudo chown -R www-data:www-data storage bootstrap/cache
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart vendr-queue:*
```

### Manual rollback code

```bash
cd /var/www/vendr
git log --oneline -20    # หา commit ที่ต้องการ
git reset --hard <commit-hash>
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan queue:restart
```

### Manual restore DB

```bash
gunzip -c /var/backups/vendr/db_xxxx.sql.gz | mysql -u$USER -p$PASSWORD $DATABASE
```

---

## Post-deploy Monitoring

เปิด 3 terminal window monitor:

```bash
# Terminal 1 — Laravel log
tail -f /var/www/vendr/storage/logs/laravel.log

# Terminal 2 — Queue worker log
sudo supervisorctl tail -f vendr-queue stderr

# Terminal 3 — System log + nginx
sudo tail -f /var/log/nginx/error.log
```

### Critical Flows ที่ต้องทดสอบหลัง deploy

1. **Login → เลือก company → Dashboard** — ต้องเห็น widgets ครบ
2. **สร้าง PR** — เลขต้องเป็น `PR{YY}{MM}xxxx` (ต่อ company)
3. **Approve PR → auto-create PO** — ดู PO draft ถูกสร้าง
4. **สร้าง GR จาก PO** — เลข GR format ถูกต้อง
5. **Switch company** — session ต้องเปลี่ยน, data ต้องกรองตาม company ใหม่
6. **Admin route** — user ที่ไม่ใช่ admin ต้องได้ 403
7. **Telegram bot** — `/verify <otp>` ลองกรอกผิดดู rate limit
8. **Scheduled command** — `php artisan schedule:run` ดูผิดไหม
9. **Queue** — `php artisan queue:work --once` สักงานดู

---

## Known Issues หลัง deploy ครั้งนี้

### Issue: Queue listener create model แล้ว fail

**อาการ:** queue log มี `RuntimeException: Cannot create X without company context`

**สาเหตุ:** Listener run ใน background worker ที่ไม่มี session

**วิธีแก้ชั่วคราว:**
ก่อน create model ใน listener ให้ set session เอง:

```php
public function handle($event): void
{
    // ดึง company_id จาก event payload
    if (property_exists($event, 'companyId') && $event->companyId) {
        session()->put('company_id', $event->companyId);
    }

    // ... logic ...
}
```

### Issue: Artisan command create model → fail

**วิธีแก้:** ก่อนรัน business logic set session:

```php
session()->put('company_id', $companyId);
// ... create models ...
session()->forget('company_id');
```

---

## Troubleshooting

| อาการ | วิธีแก้ |
|------|---------|
| 500 error ทุกหน้า | `tail storage/logs/laravel.log` — rollback ถ้าแก้ไม่ได้ |
| "Cannot create without company context" ใน queue | แก้ listener ให้ set session (ดู Known Issues) |
| Filament admin ขาว/ไม่มี CSS | `php artisan filament:optimize && npm run build` |
| Queue worker ไม่ทำงาน | `supervisorctl restart vendr-queue:*` |
| Maintenance mode ค้าง | `php artisan up` |
| Session ใช้ไม่ได้ | `php artisan session:table && php artisan migrate --force` |

---

## Contacts

- Ops lead: _____
- Backup on-call: _____
- Vultr account: _____

---

## Changelog

### 2026-04-20 — Critical Security Fixes

- Fix #1: CompanySelector no longer auto-sets company_id (multi-tenancy bypass)
- Fix #2: PR/PO/GR/VA/TOR number generator scoped per company + lockForUpdate
- Fix #3: Telegram OTP brute-force rate limiting (5/10min)
- Fix #4: Admin role check uses hasRole() (respects is_active + expires_at)
