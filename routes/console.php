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
