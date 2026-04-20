<?php

namespace App\Listeners;

use App\Events\TorSubmitted;
use App\Mail\TorSubmittedMail;
use App\Models\TermsOfReference;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTorSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    public function handle(TorSubmitted $event): void
    {
        try {
            $cacheKey = "tor_submitted_notification_{$event->torId}";
            if (Cache::has($cacheKey)) {
                Log::info('Duplicate TOR submitted notification prevented', ['tor_id' => $event->torId]);
                return;
            }
            Cache::put($cacheKey, now()->toDateTimeString(), 300);

            // Resolve TOR from correct connection
            $tor = TermsOfReference::on($event->connectionName)->find($event->torId);
            if (!$tor) {
                Log::error('TOR not found for notification', ['tor_id' => $event->torId]);
                return;
            }

            $tor->load(['department']);
            $submitter = User::find($event->submitterId);

            // Find approvers
            $approvers = collect();

            // Admins
            $approvers = $approvers->merge(
                User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get()
            );

            // Procurement managers
            $approvers = $approvers->merge(
                User::whereHas('roles', fn ($q) => $q->where('name', 'procurement_manager'))->get()
            );

            // Department heads
            if ($tor->department_id) {
                $approvers = $approvers->merge(
                    User::whereHas('roles', fn ($q) => $q->where('name', 'department_head'))
                        ->where('department_id', $tor->department_id)
                        ->get()
                );
            }

            // Remove duplicates and submitter
            $approvers = $approvers->unique('id')->filter(fn ($u) => $u->id !== $event->submitterId);

            foreach ($approvers as $approver) {
                try {
                    Mail::to($approver->email)->send(
                        new TorSubmittedMail($tor, $submitter, $approver)
                    );

                    Log::info('TOR submitted notification sent', [
                        'tor_id' => $tor->id,
                        'tor_number' => $tor->tor_number,
                        'recipient' => $approver->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send TOR email notification', [
                        'tor_id' => $tor->id,
                        'recipient' => $approver->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Telegram notification
            try {
                $telegramBot = app(TelegramBotService::class);
                $telegramBot->notifyTorSubmitted($tor, $submitter);
            } catch (\Exception $e) {
                Log::error('Telegram TOR notification error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error sending TOR submitted notification', [
                'tor_id' => $event->torId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function failed(TorSubmitted $event, \Throwable $exception): void
    {
        Log::error('TOR submitted notification job failed permanently', [
            'tor_id' => $event->torId,
            'error' => $exception->getMessage(),
        ]);
    }
}
