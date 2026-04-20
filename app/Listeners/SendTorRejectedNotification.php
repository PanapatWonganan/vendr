<?php

namespace App\Listeners;

use App\Events\TorRejected;
use App\Mail\TorRejectedMail;
use App\Models\TermsOfReference;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTorRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    public function handle(TorRejected $event): void
    {
        try {
            $cacheKey = "tor_rejected_notification_{$event->torId}";
            if (Cache::has($cacheKey)) {
                Log::info('Duplicate TOR rejected notification prevented', ['tor_id' => $event->torId]);
                return;
            }
            Cache::put($cacheKey, now()->toDateTimeString(), 300);

            $tor = TermsOfReference::on($event->connectionName)->find($event->torId);
            if (!$tor) {
                Log::error('TOR not found for rejected notification', ['tor_id' => $event->torId]);
                return;
            }

            $tor->load(['department']);
            $rejector = User::find($event->rejectorId);

            // Notify TOR creator
            $creator = User::find($tor->created_by);
            if ($creator) {
                try {
                    Mail::to($creator->email)->send(
                        new TorRejectedMail($tor, $rejector, $creator, $event->reason)
                    );

                    Log::info('TOR rejected notification sent', [
                        'tor_number' => $tor->tor_number,
                        'recipient' => $creator->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send TOR rejected email', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Telegram
            try {
                $telegramBot = app(TelegramBotService::class);
                $telegramBot->notifyTorRejected($tor, $rejector, $event->reason);
            } catch (\Exception $e) {
                Log::error('Telegram TOR rejected notification error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error sending TOR rejected notification', [
                'tor_id' => $event->torId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(TorRejected $event, \Throwable $exception): void
    {
        Log::error('TOR rejected notification job failed permanently', [
            'tor_id' => $event->torId,
            'error' => $exception->getMessage(),
        ]);
    }
}
