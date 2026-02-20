<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule PO delivery reminders (runs once daily and checks multiple day ranges)
Schedule::command('delivery:send-reminders')
    ->dailyAt('09:00')
    ->description('Send PO delivery reminders for 7, 3, and 1 day warnings');

// Schedule GR (Goods Receipt) reminders
Schedule::command('gr:send-reminders --days=15')
    ->dailyAt('08:00')
    ->description('ส่งการแจ้งเตือน GR ล่วงหน้า 15 วัน');

Schedule::command('gr:send-reminders --days=7')
    ->dailyAt('08:15')
    ->description('ส่งการแจ้งเตือน GR ล่วงหน้า 7 วัน');

Schedule::command('gr:send-reminders --days=3')
    ->dailyAt('08:30')
    ->description('ส่งการแจ้งเตือน GR ล่วงหน้า 3 วัน');

Schedule::command('gr:send-reminders --days=1')
    ->dailyAt('08:45')
    ->description('ส่งการแจ้งเตือน GR ล่วงหน้า 1 วัน');

// Telegram Bot: Morning Briefing (daily summary)
Schedule::command('telegram:morning-briefing')
    ->dailyAt('08:00')
    ->description('ส่งสรุปงานประจำวันผ่าน Telegram ทุกเช้า');

// Telegram Bot: Smart Reminders (PR pending > 3 days)
Schedule::command('telegram:smart-reminders --days=3')
    ->dailyAt('10:00')
    ->description('เตือน PR ที่ค้างเกิน 3 วัน');

// Telegram Bot: Budget Alert
Schedule::command('telegram:budget-alert')
    ->dailyAt('09:00')
    ->description('แจ้งเตือนเมื่องบประมาณแผนกเกิน threshold');

// Anomaly Detection: Scan twice daily with Telegram alerts
Schedule::command('anomalies:scan --notify')
    ->dailyAt('07:00')
    ->description('สแกนความผิดปกติรอบเช้า พร้อมแจ้งเตือน Telegram');

Schedule::command('anomalies:scan --notify')
    ->dailyAt('17:00')
    ->description('สแกนความผิดปกติรอบเย็น พร้อมแจ้งเตือน Telegram');

// Telegram Bot: Weekly Digest (every Monday morning)
Schedule::command('telegram:weekly-digest')
    ->weeklyOn(1, '08:00')
    ->description('ส่งสรุปงานประจำสัปดาห์ผ่าน Telegram ทุกวันจันทร์');
