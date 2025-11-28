# Production Mode Troubleshooting Guide

## สรุปปัญหา

เมื่อตั้งค่า `APP_ENV=production` ระบบจะเกิด error:
- **Error**: `Class "view" does not exist`
- **Error**: `The /var/www/innobic/bootstrap/cache directory must be present and writable`
- **ผลลัพธ์**: เว็บไซต์แสดง HTTP 403/500 Error

## วิธีแก้ชั่วคราว (Workaround) ✅

**ใช้ได้ปกติและปลอดภัย:**

```bash
nano /var/www/innobic/.env
```

ตั้งค่า:
```
APP_ENV=local
APP_DEBUG=false
```

```bash
php artisan config:clear
systemctl restart php8.3-fpm
systemctl restart nginx
```

### ข้อดี:
- ✅ ไม่แสดง error details/stack traces
- ✅ ไม่เปิดเผย sensitive information
- ✅ ปลอดภัยเหมือน production
- ✅ ทำงานได้ปกติทุกฟีเจอร์

### ข้อเสีย:
- ⚠️ Log level อาจละเอียดกว่า (ใช้พื้นที่มากขึ้น)
- ⚠️ Performance อาจช้ากว่าเล็กน้อย
- ⚠️ ไม่ใช่ production mode แท้ๆ

---

## แนวทางแก้ปัญหาให้ Production Mode ใช้งานได้

### สาเหตุที่น่าจะเป็น:

1. **OPcache corruption** - PHP OPcache เก็บ cached bytecode ที่ผิดพลาด
2. **Config cache corruption** - Laravel config cache มีปัญหา
3. **Permission issues** - bootstrap/cache ไม่มีสิทธิ์เขียนในบางกรณี
4. **Case sensitivity** - มี class หรือ alias ที่ใช้ตัวพิมพ์เล็ก 'view' แทนที่จะเป็น 'View'

### วิธีแก้ปัญหา (ทดสอบทีละขั้น)

#### 1. ลบ OPcache และ Clear Cache ทั้งหมด

```bash
cd /var/www/innobic

# ลบ cache files ทั้งหมด
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*

# แก้ permissions
sudo chown -R www-data:www-data bootstrap/cache
sudo chown -R www-data:www-data storage
sudo chmod -R 775 bootstrap/cache
sudo chmod -R 775 storage

# Clear Laravel cache
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild autoload
composer dump-autoload

# Restart PHP-FPM
systemctl restart php8.3-fpm
systemctl restart nginx
```

#### 2. Disable OPcache ชั่วคราว

```bash
# Disable OPcache
echo "opcache.enable=0" > /etc/php/8.3/fpm/conf.d/99-disable-opcache.ini
systemctl restart php8.3-fpm
```

**ทดสอบ:**
```bash
nano /var/www/innobic/.env
# เปลี่ยนเป็น APP_ENV=production, APP_DEBUG=false

php artisan config:clear
systemctl restart php8.3-fpm
```

เปิดเว็บทดสอบ

**ถ้าใช้งานได้** = ปัญหาอยู่ที่ OPcache

**แก้ถาวร:**
```bash
# Clear OPcache ทุกครั้งที่ deploy
# เพิ่มใน deployment script:
systemctl reload php8.3-fpm
```

#### 3. ตรวจสอบ Service Provider และ Config

```bash
# หา 'view' (ตัวพิมพ์เล็ก) ใน codebase
cd /var/www/innobic
grep -r "'view'" app/Providers/
grep -r "'view'" config/
grep -r "=> 'view'" config/app.php
grep -r "\"view\"" app/Providers/
```

ถ้าเจอ ให้แก้เป็น `'View'` หรือ `View::class`

#### 4. เช็ค PHP Extensions

```bash
php -m | grep -i intl
php -m | grep -i fileinfo
php -m | grep -i mbstring
```

ถ้าขาด extension ให้ติดตั้ง:
```bash
apt install php8.3-intl php8.3-mbstring php8.3-fileinfo
systemctl restart php8.3-fpm
```

#### 5. ตรวจสอบ Laravel Version และ Dependencies

```bash
cd /var/www/innobic
composer show | grep laravel/framework
composer diagnose
composer validate
```

#### 6. Enable Debug Mode ชั่วคราวเพื่อดู Error แท้จริง

```bash
nano /var/www/innobic/.env
```

ตั้งค่า:
```
APP_ENV=production
APP_DEBUG=true  # เปิด debug ชั่วคราว
```

```bash
php artisan config:clear
systemctl restart php8.3-fpm
```

เปิดเว็บแล้วดู error message ที่แสดงออกมา จะได้รู้สาเหตุที่แท้จริง

---

## Production Mode แบบไม่ Cache (ทางเลือก)

ถ้าไม่สามารถแก้ปัญหาได้ สามารถใช้ production mode โดยไม่ cache config:

```bash
nano /var/www/innobic/.env
```

ตั้งค่า:
```
APP_ENV=production
APP_DEBUG=false
```

```bash
# Clear cache แต่ไม่ optimize
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# สำคัญ: อย่ารัน config:cache, route:cache, view:cache

systemctl restart php8.3-fpm
systemctl restart nginx
```

### ข้อดี:
- ใช้ production mode แท้
- ปลอดภัย

### ข้อเสีย:
- ช้ากว่าเพราะไม่มี config cache
- ต้อง read config files ทุกครั้ง

---

## คำสั่งที่มีประโยชน์

### ดู Error Logs

```bash
# Laravel log
tail -50 /var/www/innobic/storage/logs/laravel.log

# Nginx error log
tail -50 /var/log/nginx/innobic-error.log

# PHP-FPM log
tail -50 /var/log/php8.3-fpm.log
```

### เช็ค Configuration

```bash
# เช็ค APP_ENV และ APP_DEBUG
grep "APP_ENV\|APP_DEBUG" /var/www/innobic/.env

# เช็ค permissions
ls -la /var/www/innobic/bootstrap/cache/
ls -la /var/www/innobic/storage/
```

### Clear All Cache

```bash
cd /var/www/innobic
php artisan optimize:clear
rm -rf bootstrap/cache/*.php
composer dump-autoload
systemctl restart php8.3-fpm
systemctl restart nginx
```

---

## เปลี่ยนกลับเป็น Debug Mode

หากเกิดปัญหาและต้องการ debug:

```bash
nano /var/www/innobic/.env
```

ตั้งค่า:
```
APP_ENV=local
APP_DEBUG=true
```

```bash
php artisan config:clear
systemctl restart php8.3-fpm
systemctl restart nginx
```

---

## Checklist สำหรับ Production Deployment

- [ ] Backup .env file ก่อนแก้ไข
- [ ] ตรวจสอบ permissions (www-data:www-data)
- [ ] Clear cache ทั้งหมดก่อน deploy
- [ ] ทดสอบใน staging environment ก่อน
- [ ] เตรียมแผน rollback
- [ ] Monitor logs หลัง deploy
- [ ] ทดสอบทุกฟีเจอร์หลัก

---

## สรุป

**สำหรับตอนนี้:**
ใช้ `APP_ENV=local` + `APP_DEBUG=false` ไปก่อน (ปลอดภัยและใช้งานได้)

**สำหรับอนาคต:**
ลองแก้ปัญหา OPcache และ config cache ตามขั้นตอนด้านบน

**หากมีปัญหา:**
ดู error logs และติดต่อ Laravel support หรือ developer

---

**Server Information:**
- **Server IP:** 207.148.120.88
- **Domain:** https://innobicprocurement.com/
- **Project Path:** /var/www/innobic
- **PHP Version:** 8.3
- **Web Server:** Nginx
- **DNS:** Cloudflare

**วันที่สร้าง:** 2025-10-21
