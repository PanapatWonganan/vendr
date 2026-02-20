<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramSmartReminders extends Command
{
    protected $signature = 'telegram:smart-reminders {--days=3 : Number of days threshold}';
    protected $description = 'Remind approvers about PRs pending longer than threshold days';

    public function handle(TelegramBotService $bot): int
    {
        $days = (int) $this->option('days');

        $stalePRs = PurchaseRequisition::where('status', 'pending_approval')
            ->where(function ($q) use ($days) {
                $q->where('submitted_at', '<=', now()->subDays($days))
                  ->orWhere(function ($q2) use ($days) {
                      $q2->whereNull('submitted_at')
                          ->where('updated_at', '<=', now()->subDays($days));
                  });
            })
            ->orderBy('priority', 'desc')
            ->orderBy('submitted_at', 'asc')
            ->get();

        if ($stalePRs->isEmpty()) {
            $this->info('No stale PRs found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$stalePRs->count()} PR(s) pending > {$days} days.");

        // Group approvers and their relevant PRs
        $approverPRs = [];

        foreach ($stalePRs as $pr) {
            $approvers = $this->getApproversForPR($pr);
            foreach ($approvers as $approver) {
                if ($approver->telegram_chat_id) {
                    $approverPRs[$approver->id] ??= [
                        'user' => $approver,
                        'prs' => [],
                    ];
                    $approverPRs[$approver->id]['prs'][] = $pr;
                }
            }
        }

        $sentCount = 0;

        foreach ($approverPRs as $data) {
            try {
                $user = $data['user'];
                $prs = $data['prs'];

                $message = $this->buildReminderMessage($prs, $days);

                // Add approve/reject buttons for each PR
                $buttons = [];
                foreach (array_slice($prs, 0, 5) as $pr) { // Max 5 buttons
                    $buttons[] = [
                        ['text' => "✅ {$pr->pr_number}", 'callback_data' => "approve_pr:{$pr->id}"],
                        ['text' => "❌ {$pr->pr_number}", 'callback_data' => "reject_pr:{$pr->id}"],
                    ];
                }

                $bot->sendMessage($user->telegram_chat_id, $message, ['inline_keyboard' => $buttons]);
                $sentCount++;

                Log::info("Smart reminder sent", [
                    'user' => $user->email,
                    'pr_count' => count($prs),
                ]);
            } catch (\Exception $e) {
                Log::error("Smart reminder error: {$e->getMessage()}");
            }
        }

        $this->info("Reminders sent to {$sentCount} approvers.");
        return Command::SUCCESS;
    }

    protected function buildReminderMessage(array $prs, int $days): string
    {
        $lines = [];
        $lines[] = "⏰ <b>แจ้งเตือน: PR รออนุมัตินานเกิน {$days} วัน</b>";
        $lines[] = "";

        $priorityIcons = ['urgent' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];

        foreach ($prs as $pr) {
            $submittedAt = $pr->submitted_at ?? $pr->updated_at;
            $waitDays = $submittedAt ? now()->diffInDays($submittedAt) : '?';
            $icon = $priorityIcons[$pr->priority] ?? '📄';
            $requester = $pr->requester->name ?? 'N/A';
            $amount = number_format($pr->total_amount ?? 0, 2);

            $lines[] = "{$icon} <b>{$pr->pr_number}</b> (รอ {$waitDays} วัน)";
            $lines[] = "   📝 {$pr->title}";
            $lines[] = "   👤 ผู้ขอ: {$requester} | 💰 {$amount} THB";
            $lines[] = "";
        }

        $lines[] = "กรุณาตรวจสอบและดำเนินการด้วยครับ";

        return implode("\n", $lines);
    }

    protected function getApproversForPR(PurchaseRequisition $pr): \Illuminate\Support\Collection
    {
        $approvers = collect();

        $approvers = $approvers->merge(
            User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get()
        );

        $approvers = $approvers->merge(
            User::whereHas('roles', fn($q) => $q->where('name', 'procurement_manager'))->get()
        );

        if ($pr->department_id) {
            $approvers = $approvers->merge(
                User::whereHas('roles', fn($q) => $q->where('name', 'department_head'))
                    ->where('department_id', $pr->department_id)
                    ->get()
            );
        }

        if ($pr->pr_approver_id) {
            $specific = User::find($pr->pr_approver_id);
            if ($specific) $approvers->push($specific);
        }

        return $approvers->unique('id');
    }
}
