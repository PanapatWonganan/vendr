#!/bin/bash
# ===================================================================
# Innobic Application Deployment Script - Phase 3
# Run this as 'innobic' user (not root)
# ===================================================================

set -e

echo "=========================================="
echo "🚀 Deploying Innobic Application"
echo "=========================================="
echo ""

# Check if running as innobic user
if [ "$USER" != "innobic" ]; then
    echo "❌ Error: This script must be run as 'innobic' user"
    echo "   Switch user: su - innobic"
    exit 1
fi

cd /var/www/innobic

# Install Composer dependencies
echo "📦 [1/8] Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Setup environment file
echo "⚙️  [2/8] Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.production .env
    echo "   ✓ .env file created from template"
else
    echo "   ⚠️  .env file already exists, skipping..."
fi

# Generate application key
echo "🔑 [3/8] Generating application key..."
php artisan key:generate --force

# Create storage link
echo "🔗 [4/8] Creating storage symbolic link..."
php artisan storage:link

# Set proper permissions
echo "🔒 [5/8] Setting file permissions..."
sudo chown -R innobic:www-data /var/www/innobic
sudo chmod -R 755 /var/www/innobic
sudo chmod -R 775 /var/www/innobic/storage
sudo chmod -R 775 /var/www/innobic/bootstrap/cache

# Run database migrations
echo "🗄️  [6/8] Running database migrations..."
read -p "⚠️  This will modify the database. Continue? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "   ✓ Migrations completed"
else
    echo "   ⚠️  Skipped migrations"
fi

# Run database seeders
echo "🌱 [7/8] Running database seeders..."
read -p "⚠️  Run seeders to create initial data? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
    echo "   ✓ Seeders completed"
    echo ""
    echo "   📋 Default admin credentials:"
    echo "      Email: admin@example.com"
    echo "      Password: password"
    echo "      ⚠️  CHANGE THIS PASSWORD IMMEDIATELY!"
    echo ""
else
    echo "   ⚠️  Skipped seeders"
fi

# Optimize Laravel
echo "⚡ [8/8] Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ""
echo "✅ Application deployment completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Configure Nginx (run as root): 04-configure-nginx.sh"
echo "   2. Install SSL certificate"
echo "   3. Setup background services"
echo ""
