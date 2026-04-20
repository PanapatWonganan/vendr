<?php

namespace App\Listeners;

use App\Events\TorApproved;
use App\Mail\TorApprovedMail;
use App\Models\TermsOfReference;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTorApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    public function handle(TorApproved $event): void
    {
        try {
            $cacheKey = "tor_approved_notification_{$event->torId}";
            if (Cache::has($cacheKey)) {
                Log::info('Duplicate TOR approved notification prevented', ['tor_id' => $event->torId]);
                return;
            }
            Cache::put($cacheKey, now()->toDateTimeString(), 300);

            $tor = TermsOfReference::on($event->connectionName)->find($event->torId);
            if (!$tor) {
                Log::error('TOR not found for approved notification', ['tor_id' => $event->torId]);
                return;
            }

            $tor->load(['department']);
            $approver = User::find($event->approverId);

            // Notify TOR creator
            $creator = User::find($tor->created_by);
            if ($creator) {
                try {
                    Mail::to($creator->email)->send(
                        new TorApprovedMail($tor, $approver, $creator)
                    );

                    Log::info('TOR approved notification sent', [
                        'tor_number' => $tor->tor_number,
                        'recipient' => $creator->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send TOR approved email', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Telegram
            try {
                $telegramBot = app(TelegramBotService::class);
                $telegramBot->notifyTorApproved($tor, $approver);
            } catch (\Exception $e) {
                Log::error('Telegram TOR approved notification error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error sending TOR approved notification', [
                'tor_id' => $event->torId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(TorApproved $event, \Throwable $exception): void
    {
        Log::error('TOR approved notification job failed permanently', [
            'tor_id' => $event->torId,
            'error' => $exception->getMessage(),
        ]);
    }
}
