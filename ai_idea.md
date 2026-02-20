# คำแนะนำการ Deploy Innobic ไปยัง Production (Vultr + Ubuntu)

## 📋 สิ่งที่ต้องเตรียมก่อน Deploy

### 1. ข้อมูล Server
- IP Address ของ Vultr server
- SSH Key หรือ Password
- User ที่มีสิทธิ์ sudo

### 2. ข้อมูล Application
- Git repository: https://github.com/PanapatWonganan/vendr.git
- Branch: main
- Application directory: `/var/www/innobic` (ปรับได้ตามต้องการ)

### 3. ตรวจสอบ Software ที่ติดตั้งแล้วบน Production
- ✅ PHP 8.2 (หรือสูงกว่า)
- ✅ Composer
- ✅ MySQL/MariaDB
- ✅ Nginx
- ✅ Git
- ✅ Supervisor (สำหรับ Queue Workers - optional)

---

## 🚀 ขั้นตอนการ Deploy

### สำหรับครั้งแรก (First-time Setup)

#### 1. SSH เข้า Production Server
```bash
ssh user@your-server-ip
```

#### 2. Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/PanapatWonganan/vendr.git innobic
cd innobic
```

#### 3. ตั้งค่า Permissions
```bash
sudo chown -R www-data:www-data /var/www/innobic
sudo chmod -R 775 storage bootstrap/cache
```

#### 4. สร้าง .env File
```bash
sudo cp .env.example .env
sudo nano .env
```

ตั้งค่าต่อไปนี้ใน `.env`:
```env
APP_NAME="Innobic Procurement System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=innobic_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Cache & Session (แนะนำ Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 5. Install Dependencies
```bash
cd /var/www/innobic
sudo -u www-data composer install --optimize-autoloader --no-dev
```

#### 6. Generate Application Key
```bash
sudo -u www-data php artisan key:generate
```

#### 7. สร้าง Database
```bash
mysql -u root -p
```

```sql
CREATE DATABASE innobic_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'innobic_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON innobic_production.* TO 'innobic_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 8. Run Migrations & Seeders
```bash
cd /var/www/innobic
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force
```

#### 9. Create Storage Link
```bash
sudo -u www-data php artisan storage:link
```

#### 10. Optimize Laravel
```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

#### 11. ตั้งค่า Nginx
สร้างไฟล์ `/etc/nginx/sites-available/innobic`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/innobic/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/innobic /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### 12. ติดตั้ง SSL Certificate (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### 13. ตั้งค่า Queue Workers (Supervisor)
สร้างไฟล์ `/etc/supervisor/conf.d/innobic-worker.conf`:

```ini
[program:innobic-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/innobic/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/innobic/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start innobic-worker:*
```

#### 14. ตั้งค่า Cron (Laravel Scheduler)
```bash
sudo crontab -e -u www-data
```

เพิ่มบรรทัดนี้:
```cron
* * * * * cd /var/www/innobic && php artisan schedule:run >> /dev/null 2>&1
```

---

### สำหรับการ Deploy ครั้งถัดไป (Updates)

เมื่อมีการอัพเดทโค้ดใหม่ (เหมือนตอนนี้ที่เพิ่ม SLA System):

#### วิธีที่ 1: ใช้ Deploy Script (แนะนำ - ง่ายที่สุด)

1. **Upload script ไปยัง server:**
```bash
scp deploy.sh user@your-server-ip:/var/www/innobic/
```

2. **SSH เข้า server และรัน script:**
```bash
ssh user@your-server-ip
cd /var/www/innobic
sudo chmod +x deploy.sh
sudo ./deploy.sh
```

Script จะทำทุกอย่างอัตโนมัติ:
- ✅ Backup database, .env, storage
- ✅ Enable maintenance mode
- ✅ Pull latest code
- ✅ Update dependencies
- ✅ Run migrations
- ✅ Run SLA backfill
- ✅ Clear & rebuild cache
- ✅ Restart services
- ✅ Disable maintenance mode
- ✅ Run tests

**เสร็จแล้ว! 🎉**

---

#### วิธีที่ 2: Manual Deployment (ทำเองทีละขั้นตอน)

1. **SSH เข้า Server**
```bash
ssh user@your-server-ip
cd /var/www/innobic
```

2. **Backup Database**
```bash
mysqldump -u innobic_user -p innobic_production > /var/backups/innobic_backup_$(date +%Y%m%d_%H%M%S).sql
```

3. **Backup .env**
```bash
cp .env /var/backups/.env_backup_$(date +%Y%m%d_%H%M%S)
```

4. **Enable Maintenance Mode**
```bash
sudo -u www-data php artisan down --retry=60
```

5. **Pull Latest Code**
```bash
sudo -u www-data git pull origin main
```

6. **Update Composer Dependencies**
```bash
sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction
```

7. **Run Migrations**
```bash
sudo -u www-data php artisan migrate --force
```

8. **Run SLA Backfill** (สำหรับครั้งนี้เท่านั้น)
```bash
sudo -u www-data php artisan sla:backfill
```

9. **Clear & Rebuild Cache**
```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

10. **Fix Permissions**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

11. **Restart Services**
```bash
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart all
sudo systemctl reload nginx
```

12. **Disable Maintenance Mode**
```bash
sudo -u www-data php artisan up
```

13. **Monitor Logs**
```bash
tail -f storage/logs/laravel.log
```

---

## ✅ Post-Deployment Verification

### 1. ทดสอบ Application
1. เข้าสู่ระบบ: `https://yourdomain.com/admin`
2. ตรวจสอบ Dashboard → ควรเห็น **SLA Performance Overview** widget
3. ไปที่ **Reports & Analytics** → **SLA Reports** → ควรเห็นรายงาน SLA
4. สร้าง PR ใหม่ → ควรเห็นฟิลด์ **แบบฟอร์ม** (Form Category)
5. สร้าง PO จาก PR → ฟิลด์ **แบบฟอร์ม** ควร auto-fill
6. เพิ่ม Vendor ใหม่ → ควรสามารถใส่ Tax ID ซ้ำได้
7. ไปที่หน้า Login → **ไม่ควรมีลิงก์ Register**

### 2. ตรวจสอบ Logs
```bash
# Laravel Log
tail -f /var/www/innobic/storage/logs/laravel.log

# Nginx Error Log
tail -f /var/log/nginx/error.log

# PHP-FPM Log
tail -f /var/log/php8.2-fpm.log
```

### 3. ตรวจสอบ Queue Workers
```bash
sudo supervisorctl status
```

ควรเห็น:
```
innobic-worker:innobic-worker_00   RUNNING
innobic-worker:innobic-worker_01   RUNNING
```

### 4. ตรวจสอบ Performance
- ทดสอบความเร็วในการโหลดหน้า
- ตรวจสอบ memory usage: `free -h`
- ตรวจสอบ disk space: `df -h`

---

## 🔄 Rollback (กรณีมีปัญหา)

### ถ้า Deployment ล้มเหลว:

1. **Restore Database**
```bash
# หา backup file ล่าสุด
ls -lt /var/backups/ | head

# Restore
mysql -u innobic_user -p innobic_production < /var/backups/innobic_backup_YYYYMMDD_HHMMSS.sql
```

2. **Restore .env**
```bash
cp /var/backups/.env_backup_YYYYMMDD_HHMMSS /var/www/innobic/.env
```

3. **Rollback Git**
```bash
cd /var/www/innobic
sudo -u www-data git reset --hard HEAD~1
```

4. **Rollback Migrations**
```bash
sudo -u www-data php artisan migrate:rollback --step=5
```

5. **Clear Cache & Restart**
```bash
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

6. **Disable Maintenance Mode**
```bash
sudo -u www-data php artisan up
```

---

## 📝 สิ่งที่เปลี่ยนแปลงใน Deployment นี้

### Features ใหม่:
1. **SLA Tracking System** ✨
   - ติดตามประสิทธิภาพการจัดซื้อ
   - แสดง grade (S, A, B, C, D, F)
   - Dashboard widget
   - รายงานละเอียด

2. **Form Category Field**
   - เพิ่มฟิลด์ "แบบฟอร์ม" ใน PR/PO
   - Auto-fill จาก PR ไป PO

3. **Vendor Tax ID**
   - อนุญาตให้ใส่ Tax ID ซ้ำได้

4. **Security**
   - ปิดระบบ registration (internal only)

### Database Migrations (5 ไฟล์):
1. `2025_11_28_170544_add_form_category_to_purchase_requisitions_table.php`
2. `2025_11_28_170642_add_form_category_to_purchase_orders_table.php`
3. `2025_11_28_171518_remove_tax_id_unique_constraint_from_vendors_table.php`
4. `2025_11_28_182326_create_sla_trackings_table.php`
5. `2025_11_28_182401_add_sla_dates_to_purchase_requisitions_and_orders.php`

### Artisan Commands ใหม่:
- `php artisan sla:backfill` - สำหรับ populate ข้อมูล SLA ย้อนหลัง

---

## 🆘 Troubleshooting

### ปัญหา: Git pull ไม่ได้
```bash
# Stash local changes
sudo -u www-data git stash

# Pull again
sudo -u www-data git pull origin main
```

### ปัญหา: Composer install ล้มเหลว
```bash
# Clear composer cache
sudo -u www-data composer clear-cache

# Try again
sudo -u www-data composer install --optimize-autoloader --no-dev
```

### ปัญหา: Migration ล้มเหลว
```bash
# ตรวจสอบ connection
sudo -u www-data php artisan db:monitor

# ดู migration status
sudo -u www-data php artisan migrate:status

# ดู error ละเอียด
sudo -u www-data php artisan migrate --force -vvv
```

### ปัญหา: Permission denied
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/innobic
sudo chmod -R 775 storage bootstrap/cache
```

### ปัญหา: 500 Internal Server Error
```bash
# ตรวจสอบ Laravel log
tail -f storage/logs/laravel.log

# ตรวจสอบ Nginx error log
tail -f /var/log/nginx/error.log

# ตรวจสอบ PHP-FPM log
tail -f /var/log/php8.2-fpm.log
```

### ปัญหา: Queue ไม่ทำงาน
```bash
# Restart queue workers
sudo supervisorctl restart innobic-worker:*

# ตรวจสอบ status
sudo supervisorctl status

# ดู worker logs
tail -f storage/logs/worker.log
```

---

## 📞 Support Contacts

- **Developer**: [Your contact]
- **Server Admin**: [Your contact]
- **Emergency**: [Your contact]

---

## 🎯 Quick Reference

### Useful Commands
```bash
# ดู application version
php artisan --version

# ดู Laravel version
php artisan about

# ดู routes
php artisan route:list

# ดู migrations status
php artisan migrate:status

# Monitor database
php artisan db:monitor

# Clear all cache
php artisan optimize:clear

# Rebuild all cache
php artisan optimize

# Run SLA backfill
php artisan sla:backfill
```

### Important Paths
- Application: `/var/www/innobic`
- Logs: `/var/www/innobic/storage/logs`
- Backups: `/var/backups/innobic`
- Nginx config: `/etc/nginx/sites-available/innobic`
- Supervisor config: `/etc/supervisor/conf.d/innobic-worker.conf`

---

**Good luck with your deployment! 🚀**

If you encounter any issues, check the logs first, then refer to the Troubleshooting section.
