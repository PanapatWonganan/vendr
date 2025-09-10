#!/bin/bash

# Script เพื่อตรวจสอบระบบอีเมลทั้งหมด
# Usage: ./check-email-system.sh

echo "🔍 Checking Email System Status..."
echo "=================================="

# 1. ตรวจสอบ Mail Configuration
echo "📧 Mail Configuration:"
echo "  - Default Mailer: $(php artisan tinker --execute="echo config('mail.default')")"
echo "  - From Address: $(php artisan tinker --execute="echo config('mail.from.address')")"
echo "  - From Name: $(php artisan tinker --execute="echo config('mail.from.name')")"
echo ""

# 2. ตรวจสอบ Queue Worker
echo "⚙️  Queue Worker Status:"
if pgrep -f "queue:work" > /dev/null; then
    echo "  ✅ Queue worker is running"
    ps aux | grep "queue:work" | grep -v grep | while read line; do
        echo "  📋 $line"
    done
else
    echo "  ❌ Queue worker is NOT running"
    echo "  💡 Run: ./start-queue-worker.sh"
fi
echo ""

# 3. ตรวจสอบ Failed Jobs
echo "📋 Queue Status:"
FAILED_JOBS=$(php artisan queue:failed | grep -c "No failed jobs")
if [ $FAILED_JOBS -eq 1 ]; then
    echo "  ✅ No failed jobs"
else
    echo "  ⚠️  There are failed jobs:"
    php artisan queue:failed | head -10
fi
echo ""

# 4. ตรวจสอบ Pending Jobs
echo "📊 Pending Jobs:"
PENDING_COUNT=$(php artisan tinker --execute="echo DB::table('jobs')->count()")
if [ -z "$PENDING_COUNT" ]; then
    echo "  📊 Pending jobs: 0"
else
    echo "  📊 Pending jobs: $PENDING_COUNT"
fi
echo ""

# 5. ตรวจสอบ Log Files
echo "📄 Recent Email Logs:"
if [ -f "storage/logs/laravel.log" ]; then
    echo "  📋 Last 5 email-related logs:"
    tail -100 storage/logs/laravel.log | grep -i "mail\|email\|purchase.*approved\|purchase.*rejected" | tail -5
else
    echo "  ⚠️  No log file found"
fi
echo ""

# 6. Quick Test
echo "🧪 Quick Email Test:"
echo "  💡 To test email: php artisan email:test your-email@example.com"
echo ""

echo "🎯 Summary:"
echo "=========="
if pgrep -f "queue:work" > /dev/null; then
    echo "✅ Email system should be working"
    echo "💡 If emails are not sent, check user preferences and logs"
else
    echo "❌ Email system is NOT working - Queue worker not running"
    echo "💡 Run: ./start-queue-worker.sh"
fi 