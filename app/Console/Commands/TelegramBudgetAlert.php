<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBudgetAlert extends Command
{
    protected $signature = 'telegram:budget-alert';
    protected $description = 'Alert department heads and admins when budget exceeds threshold';

    public function handle(TelegramBotService $bot): int
    {
        $departments = Department::active()
            ->whereNotNull('monthly_budget')
            ->where('monthly_budget', '>', 0)
            ->get();

        if ($departments->isEmpty()) {
            $this->info('No departments with budget configured.');
            return Command::SUCCESS;
        }

        $alertCount = 0;

        foreach ($departments as $department) {
            try {
                $monthlySpent = PurchaseRequisition::where('department_id', $department->id)
                    ->whereIn('status', ['approved', 'in_process', 'completed'])
                    ->whereMonth('approved_at', now()->month)
                    ->whereYear('approved_at', now()->year)
                    ->sum('total_amount');

                $percent = ($monthlySpent / $department->monthly_budget) * 100;
                $threshold = $department->budget_alert_threshold ?? 80;

                if ($percent < $threshold) {
                    continue;
                }

                $message = $this->buildAlertMessage($department, $monthlySpent, $percent);

                // Notify department manager
                $recipients = collect();

                if ($department->manager_id) {
                    $manager = User::find($department->manager_id);
                    if ($manager) $recipients->push($manager);
                }

                // Notify department heads
                $deptHeads = User::whereHas('roles', fn($q) => $q->where('name', 'department_head'))
                    ->where('department_id', $department->id)
                    ->get();
                $recipients = $recipients->merge($deptHeads);

                // Notify admins
                $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
                $recipients = $recipients->merge($admins);

                $recipients = $recipients->unique('id');

                foreach ($recipients as $user) {
                    if ($user->telegram_chat_id) {
                        $bot->sendMessage($user->telegram_chat_id, $message);
                        $alertCount++;

                        Log::info("Budget alert sent", [
                            'department' => $department->name,
                            'recipient' => $user->email,
                            'percent' => round($percent, 1),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Budget alert error for dept {$department->name}: {$e->getMessage()}");
            }
        }

        $this->info("Budget alerts sent: {$alertCount} notifications.");
        return Command::SUCCESS;
    }

    protected function buildAlertMessage(Department $department, float $spent, float $percent): string
    {
        $remaining = max(0, $department->monthly_budget - $spent);
        $percent = round($percent, 1);

        $statusIcon = $percent >= 100 ? '🚨' : ($percent >= 90 ? '🔴' : '🟡');
        $statusText = $percent >= 100 ? 'เกินงบประมาณแล้ว!' : 'ใกล้เกินงบประมาณ';

        $lines = [];
        $lines[] = "{$statusIcon} <b>แจ้งเตือนงบประมาณ: {$statusText}</b>";
        $lines[] = "";
        $lines[] = "🏢 แผนก: <b>{$department->name}</b>";
        $lines[] = "📅 เดือน: " . now()->locale('th')->translatedFormat('F Y');
        $lines[] = "";
        $lines[] = "💰 งบเดือนนี้: " . number_format($department->monthly_budget, 2) . " THB";
        $lines[] = "📊 ใช้ไปแล้ว: " . number_format($spent, 2) . " THB";
        $lines[] = "📈 คิดเป็น: <b>{$percent}%</b>";
        $lines[] = "💵 เหลือ: " . number_format($remaining, 2) . " THB";

        if ($percent >= 100) {
            $over = $spent - $department->monthly_budget;
            $lines[] = "";
            $lines[] = "🚨 <b>เกินงบ " . number_format($over, 2) . " THB</b>";
        }

        // Show top PRs this month
        $topPRs = PurchaseRequisition::where('department_id', $department->id)
            ->whereIn('status', ['approved', 'in_process', 'completed'])
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->orderBy('total_amount', 'desc')
            ->limit(3)
            ->get();

        if ($topPRs->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 <b>รายการใหญ่สุดเดือนนี้:</b>";
            foreach ($topPRs as $pr) {
                $lines[] = "   • {$pr->pr_number}: " . number_format($pr->total_amount, 2) . " THB";
            }
        }

        return implode("\n", $lines);
    }
}
