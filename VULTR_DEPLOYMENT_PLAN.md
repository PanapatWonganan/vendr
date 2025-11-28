# แผนการ Deploy Innobic Production บน Vultr Ubuntu

## 📋 ข้อมูลเบื้องต้น
- **Platform**: Vultr Cloud Compute
- **OS**: Ubuntu 22.04 LTS (แนะนำ)
- **Stack**: LEMP (Linux, Nginx, MySQL, PHP 8.2+)
- **Application**: Laravel 10.x (Filament Admin Panel)

---

## 🎯 Phase 1: การเตรียม Server (Vultr Setup)

### 1.1 สร้าง Server Instance
- เลือก **Cloud Compute** (Regular Performance หรือ High Performance)
- แนะนำขนาด: **2 CPU, 4GB RAM, 80GB SSD** ขึ้นไป
- เลือก Location: ใกล้ผู้ใช้งาน (แนะนำ Singapore สำหรับ Thailand)
- OS: **Ubuntu 22.04 LTS x64**
- เปิดใช้งาน **IPv4** และ **IPv6** (ถ้าต้องการ)
- เปิดใช้งาน **Automatic Backups** (แนะนำ)

### 1.2 ตั้งค่า Firewall พื้นฐาน
- เปิด Port: **22** (SSH), **80** (HTTP), **443** (HTTPS)
- ปิด Port อื่นๆ ที่ไม่จำเป็น
- พิจารณาใช้ Vultr Firewall Groups

### 1.3 ตั้งค่า DNS
- ชี้ Domain ไปที่ IP ของ Server
- ตั้งค่า A Record: `@` → Server IP
- ตั้งค่า A Record: `www` → Server IP
- (Optional) CNAME Record สำหรับ subdomain

---

## 🔧 Phase 2: การตั้งค่า Server พื้นฐาน

### 2.1 Initial Server Configuration
- SSH เข้า server ด้วย root
- Update package list และ upgrade packages
- ตั้งค่า timezone (Asia/Bangkok)
- ตั้งค่า hostname
- สร้าง swap file (ถ้าจำเป็น)

### 2.2 สร้าง User และตั้งค่า Security
- สร้าง non-root user สำหรับ deployment
- เพิ่ม user เข้า sudo group
- ตั้งค่า SSH key authentication
- **ปิดการ login ด้วย root** (สำคัญ!)
- **ปิดการ login ด้วย password** (ใช้ SSH key เท่านั้น)
- เปลี่ยน SSH port (optional แต่แนะนำ)

### 2.3 ติดตั้ง Fail2ban
- ป้องกันการ brute force attack
- ตั้งค่า ban IP ที่พยายาม login ผิดหลายครั้ง

---

## 📦 Phase 3: การติดตั้ง Software Stack

### 3.1 ติดตั้ง Nginx Web Server
- ติดตั้ง Nginx
- ตั้งค่า Nginx configuration พื้นฐาน
- ทดสอบการทำงาน

### 3.2 ติดตั้ง PHP 8.2+
- เพิ่ม PPA repository (ondrej/php)
- ติดตั้ง PHP-FPM
- ติดตั้ง PHP Extensions ที่จำเป็น:
  - php-mysql
  - php-mbstring
  - php-xml
  - php-bcmath
  - php-curl
  - php-zip
  - php-gd
  - php-intl
  - php-redis
  - php-imagick
- ตั้งค่า PHP configuration (php.ini)
  - upload_max_filesize
  - post_max_size
  - memory_limit
  - max_execution_time

### 3.3 ติดตั้ง MySQL 8.0
- ติดตั้ง MySQL Server
- รัน mysql_secure_installation
- สร้าง database สำหรับ Innobic
- สร้าง database user พร้อม privileges
- ตั้งค่า MySQL configuration (my.cnf)
- เปิดใช้งาน slow query log (monitoring)

### 3.4 ติดตั้ง Composer
- ดาวน์โหลดและติดตั้ง Composer (latest)
- ย้ายไปที่ /usr/local/bin สำหรับใช้งาน global

### 3.5 ติดตั้ง Redis (สำหรับ Cache & Queue)
- ติดตั้ง Redis Server
- ตั้งค่า Redis configuration
- ตั้งค่า password protection
- ทดสอบการเชื่อมต่อ

### 3.6 ติดตั้ง Supervisor (สำหรับ Queue Workers)
- ติดตั้ง Supervisor
- เตรียม configuration สำหรับ Laravel Queue

### 3.7 ติดตั้ง Git
- ติดตั้ง Git สำหรับ pull code

---

## 🚀 Phase 4: การ Deploy Laravel Application

### 4.1 เตรียม Directory Structure
- สร้างโฟลเดอร์สำหรับ application (/var/www/innobic)
- ตั้งค่า ownership และ permissions
- สร้างโฟลเดอร์สำหรับ logs, storage

### 4.2 Clone/Upload Code
**ตัวเลือก A: ใช้ Git (แนะนำ)**
- Setup Git repository (GitHub, GitLab, Bitbucket)
- Clone code จาก repository
- ตั้งค่า deployment key หรือ access token

**ตัวเลือก B: Upload ด้วย FTP/SFTP**
- Upload ไฟล์ทั้งหมดผ่าน SFTP
- (ไม่แนะนำสำหรับ production)

### 4.3 ติดตั้ง Dependencies
- รัน `composer install --optimize-autoloader --no-dev`
- ตรวจสอบว่า vendor folder ถูกสร้างเรียบร้อย

### 4.4 ตั้งค่า Environment (.env)
- Copy .env.example เป็น .env
- ตั้งค่าดังนี้:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://yourdomain.com`
  - Database credentials
  - Mail configuration (SMTP)
  - Redis configuration
  - Session/Cache drivers
  - Queue connection
  - S3/Storage configuration (ถ้ามี)
- Generate APP_KEY

### 4.5 รัน Migration & Seeder
- รัน `php artisan migrate --force`
- (ถ้าจำเป็น) รัน seeder สำหรับข้อมูลเริ่มต้น
- **สำรองฐานข้อมูลทันที**

### 4.6 ตั้งค่า Storage & Permissions
- สร้าง symbolic link: `php artisan storage:link`
- ตั้งค่า permissions:
  - storage/ → 775
  - bootstrap/cache/ → 775
  - Owner: deployment user
  - Group: www-data

### 4.7 Optimize Laravel
- รัน `php artisan config:cache`
- รัน `php artisan route:cache`
- รัน `php artisan view:cache`
- รัน `php artisan event:cache`

---

## 🌐 Phase 5: การตั้งค่า Nginx

### 5.1 สร้าง Nginx Server Block
- สร้าง configuration file ใน /etc/nginx/sites-available/
- ตั้งค่า:
  - Server name (domain)
  - Root directory → /var/www/innobic/public
  - PHP-FPM upstream
  - Laravel rewrite rules
  - Client max body size (สำหรับ file upload)
  - Gzip compression
  - Security headers
  - Rate limiting (ป้องกัน DDoS)

### 5.2 Enable Site
- สร้าง symbolic link ไปที่ sites-enabled
- ทดสอบ configuration
- Reload/Restart Nginx

### 5.3 ทดสอบ HTTP
- เข้าถึงเว็บไซต์ผ่าน http://yourdomain.com
- ตรวจสอบว่า Laravel ทำงานได้ปกติ

---

## 🔒 Phase 6: การติดตั้ง SSL Certificate

### 6.1 ติดตั้ง Certbot
- ติดตั้ง Certbot และ Nginx plugin
- ติดตั้ง Let's Encrypt certificate
- Certbot จะตั้งค่า Nginx configuration อัตโนมัติ

### 6.2 ตั้งค่า Auto-renewal
- ทดสอบ renewal process
- Certbot จะต่ออายุ certificate อัตโนมัติ

### 6.3 ทดสอบ HTTPS
- เข้าถึงเว็บไซต์ผ่าน https://yourdomain.com
- ตรวจสอบ SSL certificate
- ทดสอบด้วย SSL Labs

### 6.4 บังคับใช้ HTTPS
- Redirect HTTP → HTTPS
- ตั้งค่า HSTS header

---

## ⚙️ Phase 7: การตั้งค่า Background Services

### 7.1 ตั้งค่า Queue Workers (Supervisor)
- สร้าง Supervisor configuration สำหรับ Laravel Queue
- ตั้งค่า:
  - Command: `php artisan queue:work`
  - Number of processes
  - Auto restart
  - User: deployment user
- Start Supervisor และ Queue workers
- ทดสอบ Queue

### 7.2 ตั้งค่า Laravel Scheduler (Cron)
- เพิ่ม cron job สำหรับ Laravel Scheduler
- Command: `* * * * * php /var/www/innobic/artisan schedule:run`
- ทดสอบ scheduled tasks

---

## 🛡️ Phase 8: Security Hardening

### 8.1 Application Security
- ตรวจสอบว่า .env file ไม่สามารถเข้าถึงได้จาก web
- ตรวจสอบว่า storage/ ไม่สามารถเข้าถึงได้โดยตรง
- ตั้งค่า CORS (ถ้าจำเป็น)
- ตั้งค่า Rate Limiting
- Enable CSRF protection
- ตั้งค่า Trusted Proxies (ถ้าใช้ Cloudflare)

### 8.2 Server Security
- ติดตั้ง และตั้งค่า UFW (Uncomplicated Firewall)
- ตั้งค่า Security headers ใน Nginx:
  - X-Frame-Options
  - X-Content-Type-Options
  - X-XSS-Protection
  - Content-Security-Policy
- Disable directory listing
- Hide Nginx/PHP version

### 8.3 Database Security
- ตั้งค่าให้ MySQL listen เฉพาะ localhost
- ใช้ strong password
- จำกัด database user privileges
- Enable binary logging (สำหรับ point-in-time recovery)

---

## 💾 Phase 9: Backup Strategy

### 9.1 Database Backup
- ตั้งค่า automated MySQL dump
- Schedule: daily backup
- Retention: เก็บไว้ 7-30 วัน
- เก็บ backup file ใน:
  - Local server (temporary)
  - Remote storage (S3, Vultr Object Storage, Backblaze)

### 9.2 File Backup
- Backup storage/app (uploaded files)
- Backup .env file
- Backup Nginx/PHP configurations
- Schedule: daily/weekly

### 9.3 Backup Restoration Testing
- ทดสอบ restore backup เป็นประจำ
- จัดทำ disaster recovery plan

---

## 📊 Phase 10: Monitoring & Logging

### 10.1 Application Monitoring
- ตั้งค่า Laravel log rotation
- ติดตั้ง error tracking (แนะนำ: Sentry, Bugsnag)
- Monitor queue jobs
- Monitor scheduled tasks

### 10.2 Server Monitoring
- ติดตั้ง monitoring tools:
  - **Netdata** (free, real-time)
  - **Prometheus + Grafana**
  - หรือใช้ Vultr Monitoring
- Monitor:
  - CPU usage
  - Memory usage
  - Disk space
  - Network traffic
  - MySQL performance
  - Nginx logs

### 10.3 Uptime Monitoring
- ใช้ external monitoring service:
  - UptimeRobot (free)
  - Pingdom
  - Better Uptime
- ตั้งค่า alert notification

### 10.4 Performance Monitoring
- ใช้ Laravel Telescope (development/staging)
- Monitor slow queries
- Monitor cache hit rate
- Monitor Redis memory usage

---

## 🔄 Phase 11: CI/CD Setup (Optional แต่แนะนำ)

### 11.1 Setup Git Workflow
- สร้าง branches: main (production), staging, development
- ตั้งค่า branch protection rules

### 11.2 Setup Deployment Script
- สร้าง deployment script (bash/deployer)
- ขั้นตอน:
  1. Git pull
  2. Composer install
  3. Run migrations
  4. Clear & rebuild cache
  5. Restart queue workers
  6. Reload PHP-FPM
- ใช้ zero-downtime deployment (symlink strategy)

### 11.3 Setup CI/CD Pipeline (Optional)
- ใช้ GitHub Actions / GitLab CI / Bitbucket Pipelines
- Auto-deploy เมื่อ push ไปที่ main branch
- รัน tests ก่อน deploy

---

## 📝 Phase 12: Documentation & Maintenance

### 12.1 จัดทำเอกสาร
- Server credentials และ access information
- Database credentials
- API keys และ third-party services
- Backup & restore procedures
- Deployment procedures
- Troubleshooting guide

### 12.2 Maintenance Plan
- Schedule regular updates:
  - Ubuntu security updates (monthly)
  - PHP updates
  - Laravel updates
  - Package updates
- Schedule maintenance windows
- Monitor security advisories

---

## ✅ Phase 13: Pre-Launch Checklist

### 13.1 Performance Testing
- ทดสอบ page load speed (GTmetrix, PageSpeed Insights)
- ทดสอบ database query performance
- ทดสอบ cache ทำงานถูกต้อง
- Load testing (optional: Apache JMeter, k6)

### 13.2 Security Testing
- Scan vulnerabilities (OWASP ZAP)
- ตรวจสอบ SSL configuration
- ตรวจสอบ security headers
- Penetration testing (ถ้าจำเป็น)

### 13.3 Functionality Testing
- ทดสอบทุก features
- ทดสอบ user registration/login
- ทดสอบ email sending
- ทดสอบ file upload
- ทดสอบ purchase requisition workflow
- ทดสอบ reports และ exports
- ทดสอบ notifications

### 13.4 Browser Testing
- ทดสอบบน browsers ต่างๆ
- ทดสอบบน mobile devices
- ตรวจสอบ responsive design

---

## 🚀 Phase 14: Go Live!

### 14.1 Pre-Launch
- ประกาศ maintenance window
- Backup ทุกอย่างอีกครั้ง
- เตรียม rollback plan

### 14.2 Launch
- Switch DNS to production server (ถ้ายังไม่ได้ทำ)
- Monitor logs อย่างใกล้ชิด
- พร้อม response ต่อ issues

### 14.3 Post-Launch
- Monitor performance และ errors
- เก็บ metrics
- Collect user feedback
- Fix issues ที่พบ

---

## 🔧 Phase 15: Post-Deployment Optimization

### 15.1 Performance Optimization
- ตั้งค่า OPcache
- ตั้งค่า Redis cache
- Optimize images
- Enable HTTP/2
- Setup CDN (Cloudflare ฟรี)

### 15.2 Database Optimization
- สร้าง indexes ที่จำเป็น
- Optimize queries
- Setup read replicas (ถ้าจำเป็น)

---

## 📞 Support & Escalation

### Emergency Contacts
- Server admin
- Database admin
- Application developer
- Vultr support

### Monitoring Alerts
- Setup alert notifications (Email, SMS, Slack)
- Define alert thresholds
- Setup on-call rotation

---

## 💰 Cost Estimation (Vultr)

### Minimum Setup
- **Cloud Compute**: 2 CPU, 4GB RAM, 80GB SSD ~ $18/month
- **Backups**: $3.60/month (20% of compute)
- **Bandwidth**: 3TB included
- **Total**: ~$21.60/month

### Recommended Setup
- **Cloud Compute**: 4 CPU, 8GB RAM, 160GB SSD ~ $48/month
- **Backups**: $9.60/month
- **Object Storage**: 1TB ~ $5/month (สำหรับ backup)
- **Total**: ~$62.60/month

---

## 📚 Additional Resources

### Tools & Services ที่แนะนำ
1. **Forge** (Laravel Forge) - Auto-provision & deployment (~$15/month)
2. **Envoyer** - Zero-downtime deployment (~$10/month)
3. **Ploi** - Alternative to Forge (~$10/month)
4. **RunCloud** - Server management (~$8/month)

### Manual vs Automated
- **Manual**: ใช้เวลามากแต่ไม่มีค่าใช้จ่ายเพิ่ม
- **Forge/Ploi**: ประหยัดเวลามาก แนะนำถ้าไม่ชำนาญ DevOps

---

## ⚠️ Important Notes

1. **อย่ารัน `php artisan migrate:fresh`** บน production (จะลบข้อมูลทั้งหมด!)
2. **Backup ก่อนทำอะไรทุกครั้ง**
3. **ทดสอบบน staging environment ก่อน**
4. **ใช้ strong passwords ทุกที่**
5. **เก็บ credentials ให้ปลอดภัย**
6. **Monitor logs เป็นประจำ**
7. **Update security patches ทันที**

---

## 🎯 Timeline Estimate

- **Phase 1-3**: 2-3 ชั่วโมง (Server setup & software installation)
- **Phase 4-7**: 2-3 ชั่วโมง (Application deployment & services)
- **Phase 8-10**: 1-2 ชั่วโมง (Security & monitoring)
- **Phase 11-15**: 2-4 ชั่วโมง (CI/CD & optimization)

**Total**: 7-12 ชั่วโมง (สำหรับคนที่มีประสบการณ์)

หรือใช้ **Laravel Forge**: 30 นาที - 1 ชั่วโมง (แนะนำ!)

---

**Good luck with your deployment! 🚀**
