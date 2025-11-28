# 🚀 Innobic Production Deployment Guide

**Server:** Vultr Ubuntu 22.04
**IP:** 207.148.120.88
**Domain:** innobicprocurement.com
**User:** root → innobic (deployment user)

---

## 📋 Pre-Deployment Checklist

- [x] Vultr server created (207.148.120.88)
- [x] Domain registered (innobicprocurement.com)
- [x] DNS configured on Cloudflare
- [ ] Code ready to upload
- [ ] Database credentials secured

---

## 🎯 Step-by-Step Deployment

### **STEP 1: SSH into Server**

```bash
# From your Mac terminal
ssh root@207.148.120.88
# Password: =z8T_W6]7mJ@vb8$
```

---

### **STEP 2: Upload Deployment Scripts**

**Option A: Using SCP (Recommended)**

```bash
# On your Mac (in project directory)
cd /Users/janejiramalai/development/Innobic

# Upload deployment folder to server
scp -r deployment root@207.148.120.88:/root/
```

**Option B: Clone from Git (if you have repo)**

```bash
# On server
cd /root
git clone YOUR_REPO_URL innobic-deploy
cd innobic-deploy
```

---

### **STEP 3: Run Server Setup Script**

```bash
# On server (as root)
cd /root/deployment
chmod +x *.sh
bash 01-server-setup.sh
```

**What this does:**
- ✅ Updates system packages
- ✅ Sets timezone to Bangkok
- ✅ Creates 'innobic' deployment user
- ✅ Configures firewall (UFW)
- ✅ Installs Fail2ban
- ✅ Creates application directory

**⏱️ Takes: ~5 minutes**

---

### **STEP 4: Setup SSH Key (IMPORTANT!)**

```bash
# On your Mac, generate SSH key if you don't have one
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# Copy your public key
cat ~/.ssh/id_rsa.pub

# On server, add it to authorized_keys
mkdir -p /home/innobic/.ssh
nano /home/innobic/.ssh/authorized_keys
# Paste your public key, save (Ctrl+X, Y, Enter)

chmod 600 /home/innobic/.ssh/authorized_keys
chmod 700 /home/innobic/.ssh
chown -R innobic:innobic /home/innobic/.ssh
```

**Test SSH login:**

```bash
# From your Mac
ssh innobic@207.148.120.88
# Should login without password!
```

---

### **STEP 5: Install Software Stack**

```bash
# On server (as root)
cd /root/deployment
bash 02-install-software.sh
```

**What this installs:**
- ✅ Nginx web server
- ✅ PHP 8.3 + extensions
- ✅ MySQL 8.0
- ✅ Redis
- ✅ Composer
- ✅ Supervisor

**⏱️ Takes: ~10-15 minutes**

**⚠️ SAVE THESE CREDENTIALS:**
```
MySQL root password: TempPassword123!
Database: innobic_production
DB User: innobic_user
DB Password: Innobic@2025Secure!
```

---

### **STEP 6: Upload Application Code**

**Option A: Using Git (Recommended)**

```bash
# On server (as innobic user)
su - innobic
cd /var/www/innobic

# Clone your repository
git clone YOUR_REPO_URL .

# Or if already cloned
git pull origin main
```

**Option B: Using SCP**

```bash
# On your Mac
cd /Users/janejiramalai/development/Innobic

# Upload to server (exclude unnecessary files)
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' \
  ./ innobic@207.148.120.88:/var/www/innobic/
```

---

### **STEP 7: Deploy Application**

```bash
# On server (as innobic user)
su - innobic
cd /var/www/innobic

# Make sure deployment scripts are executable
chmod +x deployment/*.sh

# Run deployment script
bash deployment/03-deploy-application.sh
```

**What this does:**
- ✅ Installs Composer dependencies
- ✅ Sets up .env file
- ✅ Generates APP_KEY
- ✅ Creates storage link
- ✅ Sets file permissions
- ✅ Runs database migrations
- ✅ Runs seeders (optional)
- ✅ Optimizes Laravel caches

**⏱️ Takes: ~5-10 minutes**

**📝 Default Admin Credentials:**
```
Email: admin@example.com
Password: password
⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN!
```

---

### **STEP 8: Configure Nginx**

```bash
# On server (as root - exit innobic user first)
exit
# Now you're root again

cd /root/deployment
bash 04-configure-nginx.sh
```

**What this does:**
- ✅ Installs Nginx site configuration
- ✅ Enables the site
- ✅ Tests configuration
- ✅ Restarts Nginx

**⏱️ Takes: ~1 minute**

**🧪 Test your site:**
```
Open browser: http://innobicprocurement.com
```

---

### **STEP 9: Install SSL Certificate**

```bash
# On server (as root)
cd /root/deployment
bash 05-install-ssl.sh
```

**What this does:**
- ✅ Installs Certbot
- ✅ Obtains Let's Encrypt SSL certificate
- ✅ Configures auto-renewal
- ✅ Updates Nginx to use HTTPS

**⏱️ Takes: ~2-3 minutes**

**🔒 After SSL installation:**
1. Go to Cloudflare Dashboard
2. SSL/TLS → Overview
3. Change to: **Full (strict)**

**🧪 Test HTTPS:**
```
Open browser: https://innobicprocurement.com
```

---

### **STEP 10: Setup Background Services**

```bash
# On server (as root)
cd /root/deployment
bash 06-setup-services.sh
```

**What this does:**
- ✅ Configures Supervisor for Queue Workers
- ✅ Sets up Laravel Scheduler (Cron)
- ✅ Starts background services

**⏱️ Takes: ~2 minutes**

---

## ✅ Post-Deployment Checklist

### **Security**

- [ ] Change default admin password
- [ ] Change MySQL root password
- [ ] Update .env file with secure credentials
- [ ] Verify firewall rules: `sudo ufw status`
- [ ] Check Fail2ban: `sudo fail2ban-client status`

### **Testing**

- [ ] Test login: admin@example.com / password
- [ ] Create a test purchase requisition
- [ ] Upload a file/attachment
- [ ] Send a test email
- [ ] Check queue workers: `supervisorctl status`
- [ ] Check logs: `tail -f /var/www/innobic/storage/logs/laravel.log`

### **Cloudflare**

- [ ] SSL/TLS mode: Full (strict)
- [ ] Enable Bot Fight Mode
- [ ] Enable Browser Integrity Check
- [ ] Security Level: Medium or High
- [ ] Enable Auto Minify (HTML, CSS, JS)
- [ ] Enable Brotli

### **Monitoring**

- [ ] Setup uptime monitoring (UptimeRobot)
- [ ] Configure error notifications
- [ ] Setup database backup schedule

---

## 🔧 Common Commands

### **Service Management**

```bash
# Nginx
sudo systemctl status nginx
sudo systemctl restart nginx
sudo nginx -t  # Test config

# PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# MySQL
sudo systemctl status mysql
mysql -u innobic_user -p innobic_production

# Redis
sudo systemctl status redis-server
redis-cli ping

# Queue Workers
sudo supervisorctl status innobic-worker:*
sudo supervisorctl restart innobic-worker:*
```

### **Laravel Commands**

```bash
# As innobic user
cd /var/www/innobic

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Check queue
php artisan queue:work --once
```

### **Logs**

```bash
# Application logs
tail -f /var/www/innobic/storage/logs/laravel.log

# Worker logs
tail -f /var/www/innobic/storage/logs/worker.log

# Nginx logs
tail -f /var/log/nginx/innobic-access.log
tail -f /var/log/nginx/innobic-error.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### **File Permissions (if needed)**

```bash
sudo chown -R innobic:www-data /var/www/innobic
sudo chmod -R 755 /var/www/innobic
sudo chmod -R 775 /var/www/innobic/storage
sudo chmod -R 775 /var/www/innobic/bootstrap/cache
```

---

## 🆘 Troubleshooting

### **500 Internal Server Error**

```bash
# Check logs
tail -f /var/www/innobic/storage/logs/laravel.log

# Check permissions
ls -la /var/www/innobic/storage

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Queue Jobs Not Processing**

```bash
# Check worker status
sudo supervisorctl status innobic-worker:*

# Restart workers
sudo supervisorctl restart innobic-worker:*

# Check logs
tail -f /var/www/innobic/storage/logs/worker.log
```

### **Database Connection Error**

```bash
# Test connection
mysql -u innobic_user -p innobic_production

# Check .env file
cat /var/www/innobic/.env | grep DB_

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

---

## 📞 Important Contacts

**Server Info:**
- IP: 207.148.120.88
- Domain: innobicprocurement.com
- Hosting: Vultr

**Credentials:**
- Deployment user: innobic (SSH key auth)
- MySQL root: TempPassword123!
- Database user: innobic_user / Innobic@2025Secure!
- Default admin: admin@example.com / password

**Services:**
- Cloudflare: DNS + CDN + SSL
- SendGrid: Email delivery
- Let's Encrypt: SSL certificate

---

## 🎉 Deployment Complete!

Your Innobic Procurement System is now live at:
**https://innobicprocurement.com**

Next steps:
1. Change all default passwords
2. Create real user accounts
3. Configure email notifications
4. Setup automated backups
5. Monitor system health

Good luck! 🚀
