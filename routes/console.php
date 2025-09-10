<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule delivery reminders
Schedule::command('delivery:send-reminders --days=15')
    ->dailyAt('09:00')
    ->description('Send 15-day delivery reminders');

Schedule::command('delivery:send-reminders --days=7') 
    ->dailyAt('09:15')
    ->description('Send 7-day delivery reminders');

Schedule::command('delivery:send-reminders --days=1')
    ->dailyAt('09:30') 
    ->description('Send 1-day delivery reminders');

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
