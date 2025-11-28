#!/bin/bash
# ===================================================================
# Innobic Software Stack Installation - Phase 2
# Stack: Nginx, PHP 8.3, MySQL 8.0, Redis, Composer, Supervisor
# ===================================================================

set -e

echo "=========================================="
echo "📦 Installing Software Stack"
echo "=========================================="
echo ""

# Install Nginx
echo "🌐 [1/7] Installing Nginx..."
apt-get install -y nginx
systemctl enable nginx
systemctl start nginx

# Add PHP repository (Ondrej PPA)
echo "🐘 [2/7] Adding PHP 8.3 repository..."
add-apt-repository -y ppa:ondrej/php
apt-get update

# Install PHP 8.3 and extensions
echo "🐘 [3/7] Installing PHP 8.3 and extensions..."
apt-get install -y \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl \
    php8.3-redis \
    php8.3-imagick \
    php8.3-cli \
    php8.3-common

# Configure PHP
echo "⚙️  Configuring PHP..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

systemctl enable php8.3-fpm
systemctl restart php8.3-fpm

# Install MySQL 8.0
echo "🗄️  [4/7] Installing MySQL 8.0..."
apt-get install -y mysql-server

# Secure MySQL installation
echo "🔒 Securing MySQL installation..."
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'TempPassword123!';"
mysql -uroot -pTempPassword123! -e "DELETE FROM mysql.user WHERE User='';"
mysql -uroot -pTempPassword123! -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -uroot -pTempPassword123! -e "DROP DATABASE IF EXISTS test;"
mysql -uroot -pTempPassword123! -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -uroot -pTempPassword123! -e "FLUSH PRIVILEGES;"

echo ""
echo "⚠️  MySQL root password: TempPassword123!"
echo "   Please change this password and save it securely!"
echo ""

# Create database and user for Innobic
echo "🗄️  Creating Innobic database..."
mysql -uroot -pTempPassword123! <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS innobic_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'innobic_user'@'localhost' IDENTIFIED BY 'Innobic@2025Secure!';
GRANT ALL PRIVILEGES ON innobic_production.* TO 'innobic_user'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo ""
echo "✅ Database created:"
echo "   Database: innobic_production"
echo "   User: innobic_user"
echo "   Password: Innobic@2025Secure!"
echo ""

# Install Redis
echo "💾 [5/7] Installing Redis..."
apt-get install -y redis-server
sed -i 's/supervised no/supervised systemd/' /etc/redis/redis.conf
systemctl enable redis-server
systemctl restart redis-server

# Install Composer
echo "🎼 [6/7] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Supervisor
echo "👷 [7/7] Installing Supervisor..."
apt-get install -y supervisor
systemctl enable supervisor
systemctl start supervisor

echo ""
echo "✅ Software stack installation completed!"
echo ""
echo "📋 Installed:"
echo "   ✓ Nginx"
echo "   ✓ PHP 8.3 + Extensions"
echo "   ✓ MySQL 8.0"
echo "   ✓ Redis"
echo "   ✓ Composer"
echo "   ✓ Supervisor"
echo ""
echo "🔐 IMPORTANT - Save these credentials:"
echo "   MySQL root password: TempPassword123!"
echo "   Database: innobic_production"
echo "   DB User: innobic_user"
echo "   DB Password: Innobic@2025Secure!"
echo ""
echo "📋 Next: Upload application code and run 03-deploy-application.sh"
echo ""
