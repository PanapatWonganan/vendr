#!/bin/bash
# ===================================================================
# Innobic Background Services Setup - Phase 6
# Queue Workers, Scheduler
# Run this as root
# ===================================================================

set -e

echo "=========================================="
echo "⚙️  Setting up Background Services"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root"
    exit 1
fi

# Setup Supervisor for Queue Workers
echo "👷 [1/3] Setting up Supervisor for Queue Workers..."
cp /var/www/innobic/deployment/supervisor-innobic-worker.conf /etc/supervisor/conf.d/innobic-worker.conf

# Reload Supervisor
supervisorctl reread
supervisorctl update
supervisorctl start innobic-worker:*

echo "   ✓ Queue workers started"

# Setup Laravel Scheduler (Cron)
echo "⏰ [2/3] Setting up Laravel Scheduler..."
CRON_LINE="* * * * * cd /var/www/innobic && php artisan schedule:run >> /dev/null 2>&1"

# Add to innobic user's crontab
(crontab -u innobic -l 2>/dev/null; echo "$CRON_LINE") | crontab -u innobic -

echo "   ✓ Scheduler configured"

# Test services
echo "🧪 [3/3] Testing services..."
echo ""
echo "Queue Workers Status:"
supervisorctl status innobic-worker:*
echo ""
echo "Cron Jobs:"
crontab -u innobic -l | grep artisan
echo ""

echo "✅ Background services setup completed!"
echo ""
echo "📋 Service Management Commands:"
echo "   Queue Workers:"
echo "   - supervisorctl status innobic-worker:*"
echo "   - supervisorctl restart innobic-worker:*"
echo "   - supervisorctl stop innobic-worker:*"
echo ""
echo "   Logs:"
echo "   - tail -f /var/www/innobic/storage/logs/laravel.log"
echo "   - tail -f /var/www/innobic/storage/logs/worker.log"
echo ""
