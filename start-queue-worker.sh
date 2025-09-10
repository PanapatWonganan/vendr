#!/bin/bash

# Script เพื่อเริ่ม Laravel Queue Worker
# Usage: ./start-queue-worker.sh

echo "🚀 Starting Laravel Queue Worker..."

# ตรวจสอบว่า queue worker ทำงานอยู่หรือไม่
if pgrep -f "queue:work" > /dev/null; then
    echo "⚠️  Queue worker is already running!"
    echo "📋 Current processes:"
    ps aux | grep "queue:work" | grep -v grep
    exit 1
fi

# เริ่ม queue worker
echo "📨 Starting queue worker for email processing..."
php artisan queue:work --daemon --tries=3 --timeout=60 &

# แสดงสถานะ
sleep 2
if pgrep -f "queue:work" > /dev/null; then
    echo "✅ Queue worker started successfully!"
    echo "📋 Process info:"
    ps aux | grep "queue:work" | grep -v grep
    echo ""
    echo "💡 Tips:"
    echo "  - To stop: pkill -f 'queue:work'"
    echo "  - To check status: ps aux | grep 'queue:work'"
    echo "  - To check logs: tail -f storage/logs/laravel.log"
else
    echo "❌ Failed to start queue worker!"
    exit 1
fi 