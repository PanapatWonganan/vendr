<?php

namespace App\Console\Commands;

use App\Models\ProcurementAnomaly;
use App\Models\User;
use App\Services\AnomalyDetectionService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanAnomalies extends Command
{
    protected $signature = 'anomalies:scan
                            {--company= : Scan specific company ID only}
                            {--notify : Send Telegram alerts for new critical/warning anomalies}
                            {--dry-run : Show what would be detected without saving}';

    protected $description = 'Scan for procurement anomalies (price, split PR, budget, vendor concentration, delays)';

    public function handle(AnomalyDetectionService $service): int
    {
        $this->info('Starting anomaly scan...');

        $companyId = $this->option('company');

        if ($companyId) {
            $results = [$companyId => $service->scan((int) $companyId)];
        } else {
            $results = $service->scanAll();
        }

        $totalNew = 0;
        foreach ($results as $cId => $anomalies) {
            $count = count($anomalies);
            $totalNew += $count;
            if ($count > 0) {
                $this->info("  Company #{$cId}: {$count} new anomalies");
                foreach ($anomalies as $anomaly) {
                    $icon = match ($anomaly->severity) {
                        'critical' => '!!!',
                        'warning' => ' !!',
                        default => '  i',
                    };
                    $this->line("    [{$icon}] [{$anomaly->type}] {$anomaly->title}");
                }
            } else {
                $this->line("  Company #{$cId}: No new anomalies");
            }
        }

        $this->newLine();
        $this->info("Scan complete. {$totalNew} new anomalies found.");

        // Send Telegram notifications if requested
        if ($this->option('notify') && $totalNew > 0) {
            $this->notifyViaGram($results);
        }

        return self::SUCCESS;
    }

    /**
     * Send Telegram notifications for critical/warning anomalies
     */
    protected function notifyViaGram(array $results): void
    {
        $this->info('Sending Telegram alerts...');

        $telegram = app(TelegramBotService::class);
        $notifiedCount = 0;

        foreach ($results as $companyId => $anomalies) {
            // Filter only critical and warning
            $alertable = collect($anomalies)->filter(
                fn ($a) => in_array($a->severity, [ProcurementAnomaly::SEVERITY_CRITICAL, ProcurementAnomaly::SEVERITY_WARNING])
            );

            if ($alertable->isEmpty()) continue;

            // Build message
            $text = "🚨 <b>Anomaly Alert</b>\n";
            $text .= "พบความผิดปกติ {$alertable->count()} รายการ\n\n";

            foreach ($alertable as $anomaly) {
                $icon = $anomaly->severity === 'critical' ? '🔴' : '🟡';
                $text .= "{$icon} <b>{$anomaly->type_label}</b>\n";
                $text .= "{$anomaly->title}\n";
                $text .= "<i>" . mb_substr($anomaly->description, 0, 150) . "</i>\n\n";
            }

            $text .= "📊 ดูรายละเอียดที่ Admin > Anomaly Detection";

            // Send to managers and admins who have Telegram linked
            $recipients = User::whereNotNull('telegram_chat_id')
                ->where(function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->whereIn('name', ['admin', 'procurement_manager']);
                    });
                })
                ->get();

            foreach ($recipients as $user) {
                try {
                    $telegram->sendMessage($user->telegram_chat_id, $text);
                    $notifiedCount++;
                } catch (\Exception $e) {
                    Log::warning("Failed to notify user {$user->id}: {$e->getMessage()}");
                }
            }

            // Mark anomalies as notified
            foreach ($alertable as $anomaly) {
                $anomaly->markNotified();
            }
        }

        $this->info("Notified {$notifiedCount} users via Telegram.");
    }
}
