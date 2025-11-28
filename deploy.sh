#!/bin/bash

###############################################################################
# Innobic Production Deployment Script for Vultr Ubuntu Server
# Version: 1.0
# Description: Automated deployment with safety checks and rollback capability
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration - ปรับตามสภาพแวดล้อมของคุณ
APP_DIR="/var/www/innobic"
APP_USER="www-data"
PHP_VERSION="8.2"
BACKUP_DIR="/var/backups/innobic"
DATE=$(date +%Y%m%d_%H%M%S)

# Functions
print_header() {
    echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Check if script is run as root or with sudo
check_permissions() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

# Create backup directory if not exists
prepare_backup_dir() {
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        print_success "Created backup directory: $BACKUP_DIR"
    fi
}

# Backup database
backup_database() {
    print_header "1. Backing up database..."

    cd "$APP_DIR" || exit

    # Get DB credentials from .env
    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

    BACKUP_FILE="$BACKUP_DIR/database_backup_$DATE.sql"

    if mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null; then
        gzip "$BACKUP_FILE"
        print_success "Database backed up to: ${BACKUP_FILE}.gz"
    else
        print_error "Database backup failed!"
        exit 1
    fi
}

# Backup .env file
backup_env() {
    print_header "2. Backing up .env file..."

    if cp "$APP_DIR/.env" "$BACKUP_DIR/.env_backup_$DATE"; then
        print_success ".env backed up to: $BACKUP_DIR/.env_backup_$DATE"
    else
        print_error ".env backup failed!"
        exit 1
    fi
}

# Backup storage directory
backup_storage() {
    print_header "3. Backing up storage directory..."

    STORAGE_BACKUP="$BACKUP_DIR/storage_backup_$DATE.tar.gz"

    if tar -czf "$STORAGE_BACKUP" -C "$APP_DIR" storage 2>/dev/null; then
        print_success "Storage backed up to: $STORAGE_BACKUP"
    else
        print_warning "Storage backup failed (non-critical)"
    fi
}

# Enable maintenance mode
enable_maintenance() {
    print_header "4. Enabling maintenance mode..."

    cd "$APP_DIR" || exit

    if sudo -u "$APP_USER" php artisan down --retry=60; then
        print_success "Maintenance mode enabled"
    else
        print_warning "Failed to enable maintenance mode"
    fi
}

# Pull latest code from Git
pull_code() {
    print_header "5. Pulling latest code from GitHub..."

    cd "$APP_DIR" || exit

    # Stash any local changes
    sudo -u "$APP_USER" git stash

    # Pull latest code
    if sudo -u "$APP_USER" git pull origin main; then
        print_success "Code pulled successfully"

        # Show latest commit
        LATEST_COMMIT=$(git log -1 --pretty=format:"%h - %s (%ar)")
        print_info "Latest commit: $LATEST_COMMIT"
    else
        print_error "Git pull failed!"
        disable_maintenance
        exit 1
    fi
}

# Install/Update Composer dependencies
update_dependencies() {
    print_header "6. Updating Composer dependencies..."

    cd "$APP_DIR" || exit

    if sudo -u "$APP_USER" composer install --optimize-autoloader --no-dev --no-interaction; then
        print_success "Composer dependencies updated"
    else
        print_error "Composer install failed!"
        disable_maintenance
        exit 1
    fi
}

# Run database migrations
run_migrations() {
    print_header "7. Running database migrations..."

    cd "$APP_DIR" || exit

    if sudo -u "$APP_USER" php artisan migrate --force; then
        print_success "Migrations completed successfully"
    else
        print_error "Migrations failed!"
        print_warning "You may need to restore the database backup and investigate"
        disable_maintenance
        exit 1
    fi
}

# Run SLA backfill command
run_sla_backfill() {
    print_header "8. Running SLA backfill (historical data)..."

    cd "$APP_DIR" || exit

    if sudo -u "$APP_USER" php artisan sla:backfill; then
        print_success "SLA backfill completed"
    else
        print_warning "SLA backfill failed (non-critical)"
    fi
}

# Clear and rebuild cache
rebuild_cache() {
    print_header "9. Clearing and rebuilding cache..."

    cd "$APP_DIR" || exit

    # Clear all caches
    sudo -u "$APP_USER" php artisan config:clear
    sudo -u "$APP_USER" php artisan cache:clear
    sudo -u "$APP_USER" php artisan view:clear
    sudo -u "$APP_USER" php artisan route:clear
    sudo -u "$APP_USER" php artisan event:clear

    print_success "All caches cleared"

    # Rebuild caches for production
    sudo -u "$APP_USER" php artisan config:cache
    sudo -u "$APP_USER" php artisan route:cache
    sudo -u "$APP_USER" php artisan view:cache
    sudo -u "$APP_USER" php artisan event:cache

    print_success "Production caches rebuilt"
}

# Fix permissions
fix_permissions() {
    print_header "10. Fixing file permissions..."

    cd "$APP_DIR" || exit

    chown -R "$APP_USER":"$APP_USER" storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache

    print_success "Permissions fixed"
}

# Restart services
restart_services() {
    print_header "11. Restarting services..."

    # Restart PHP-FPM
    if systemctl reload php${PHP_VERSION}-fpm; then
        print_success "PHP-FPM reloaded"
    else
        print_warning "PHP-FPM reload failed"
    fi

    # Restart Queue Workers (if using Supervisor)
    if command -v supervisorctl &> /dev/null; then
        if supervisorctl restart all; then
            print_success "Queue workers restarted"
        else
            print_warning "Queue workers restart failed"
        fi
    fi

    # Reload Nginx
    if systemctl reload nginx; then
        print_success "Nginx reloaded"
    else
        print_warning "Nginx reload failed"
    fi
}

# Disable maintenance mode
disable_maintenance() {
    print_header "12. Disabling maintenance mode..."

    cd "$APP_DIR" || exit

    if sudo -u "$APP_USER" php artisan up; then
        print_success "Application is now live!"
    else
        print_warning "Failed to disable maintenance mode"
    fi
}

# Run post-deployment tests
run_tests() {
    print_header "13. Running post-deployment checks..."

    cd "$APP_DIR" || exit

    # Check if artisan is working
    if sudo -u "$APP_USER" php artisan --version &> /dev/null; then
        print_success "Artisan is working"
    else
        print_error "Artisan is not working!"
    fi

    # Check database connection
    if sudo -u "$APP_USER" php artisan db:monitor &> /dev/null; then
        print_success "Database connection is working"
    else
        print_warning "Database connection check failed"
    fi
}

# Display deployment summary
show_summary() {
    print_header "DEPLOYMENT SUMMARY"

    echo -e "${GREEN}Deployment completed successfully!${NC}"
    echo ""
    echo "Deployment time: $DATE"
    echo "Backup location: $BACKUP_DIR"
    echo ""
    echo -e "${BLUE}What was deployed:${NC}"
    echo "  - SLA Tracking System"
    echo "  - Form Category field (PR/PO)"
    echo "  - Vendor Tax ID constraint removed"
    echo "  - Registration feature disabled"
    echo "  - 5 new database migrations"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "  1. Monitor application logs: tail -f $APP_DIR/storage/logs/laravel.log"
    echo "  2. Check SLA Dashboard Widget"
    echo "  3. Check SLA Reports in Reports & Analytics"
    echo "  4. Test creating new PR/PO"
    echo "  5. Verify vendor creation with duplicate Tax ID"
    echo ""
    echo -e "${BLUE}Rollback instructions (if needed):${NC}"
    echo "  1. Restore database: gunzip < $BACKUP_DIR/database_backup_$DATE.sql.gz | mysql -u[user] -p [database]"
    echo "  2. Restore .env: cp $BACKUP_DIR/.env_backup_$DATE $APP_DIR/.env"
    echo "  3. Run: git reset --hard HEAD~1"
    echo "  4. Run: php artisan migrate:rollback --step=5"
    echo ""
}

# Main deployment flow
main() {
    print_header "INNOBIC PRODUCTION DEPLOYMENT"
    print_info "Starting deployment at $(date)"
    echo ""

    check_permissions
    prepare_backup_dir

    # Phase 1: Backup
    backup_database
    backup_env
    backup_storage

    # Phase 2: Deploy
    enable_maintenance
    pull_code
    update_dependencies
    run_migrations
    run_sla_backfill
    rebuild_cache
    fix_permissions
    restart_services
    disable_maintenance

    # Phase 3: Verify
    run_tests

    # Show summary
    echo ""
    show_summary

    print_success "All done! 🚀"
}

# Run main function
main
