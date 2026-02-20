<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramMorningBriefing extends Command
{
    protected $signature = 'telegram:morning-briefing';
    protected $description = 'Send daily morning briefing to Telegram-linked users';

    public function handle(TelegramBotService $bot): int
    {
        $users = User::whereNotNull('telegram_chat_id')->get();

        if ($users->isEmpty()) {
            $this->info('No Telegram-linked users found.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($users as $user) {
            try {
                $message = $this->buildBriefing($user);
                if ($message) {
                    $bot->sendMessage($user->telegram_chat_id, $message);
                    $sentCount++;
                }
            } catch (\Exception $e) {
                Log::error("Morning briefing error for user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Morning briefing sent to {$sentCount} users.");
        return Command::SUCCESS;
    }

    protected function buildBriefing(User $user): ?string
    {
        $lines = [];
        $lines[] = "🌅 <b>สรุปงานประจำวัน</b>";
        $lines[] = "📅 " . now()->locale('th')->translatedFormat('l j F Y');
        $lines[] = "👤 สวัสดีคุณ {$user->name}";
        $lines[] = "";

        $hasContent = false;

        // --- Section: Pending Approvals (for approvers) ---
        $isApprover = $user->hasRole('admin')
            || $user->hasRole('procurement_manager')
            || $user->hasRole('department_head');

        if ($isApprover) {
            $pendingQuery = PurchaseRequisition::where('status', 'pending_approval');

            // Department heads only see their department
            if (!$user->hasRole('admin') && !$user->hasRole('procurement_manager')) {
                if ($user->department_id) {
                    $pendingQuery->where('department_id', $user->department_id);
                }
            }

            $pendingCount = $pendingQuery->count();
            $urgentCount = (clone $pendingQuery)->where('priority', 'urgent')->count();
            $pendingTotal = (clone $pendingQuery)->sum('total_amount');
            $oldestPending = (clone $pendingQuery)->orderBy('submitted_at', 'asc')->first();

            if ($pendingCount > 0) {
                $hasContent = true;
                $lines[] = "📋 <b>รออนุมัติ</b>";
                $lines[] = "   ทั้งหมด: {$pendingCount} ใบ";
                if ($urgentCount > 0) {
                    $lines[] = "   🔴 เร่งด่วน: {$urgentCount} ใบ";
                }
                $lines[] = "   💰 มูลค่ารวม: " . number_format($pendingTotal, 2) . " THB";
                if ($oldestPending && $oldestPending->submitted_at) {
                    $days = now()->diffInDays($oldestPending->submitted_at);
                    if ($days > 0) {
                        $lines[] = "   ⏰ ค้างนานสุด: {$days} วัน ({$oldestPending->pr_number})";
                    }
                }
                $lines[] = "";
            }
        }

        // --- Section: My PRs Status ---
        $myDraft = PurchaseRequisition::where('requester_id', $user->id)->where('status', 'draft')->count();
        $myPending = PurchaseRequisition::where('requester_id', $user->id)->where('status', 'pending_approval')->count();
        $myApprovedToday = PurchaseRequisition::where('requester_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('approved_at', today())
            ->count();
        $myRejectedToday = PurchaseRequisition::where('requester_id', $user->id)
            ->where('status', 'rejected')
            ->whereDate('rejected_at', today())
            ->count();

        if ($myDraft > 0 || $myPending > 0 || $myApprovedToday > 0 || $myRejectedToday > 0) {
            $hasContent = true;
            $lines[] = "📝 <b>ใบ PR ของคุณ</b>";
            if ($myDraft > 0) $lines[] = "   📄 Draft: {$myDraft} ใบ";
            if ($myPending > 0) $lines[] = "   ⏳ รออนุมัติ: {$myPending} ใบ";
            if ($myApprovedToday > 0) $lines[] = "   ✅ อนุมัติวันนี้: {$myApprovedToday} ใบ";
            if ($myRejectedToday > 0) $lines[] = "   ❌ ปฏิเสธวันนี้: {$myRejectedToday} ใบ";
            $lines[] = "";
        }

        // --- Section: Department Budget (for dept heads) ---
        if ($user->hasRole('department_head') && $user->department_id) {
            $department = $user->department;
            if ($department && $department->monthly_budget > 0) {
                $monthlySpent = PurchaseRequisition::where('department_id', $user->department_id)
                    ->whereIn('status', ['approved', 'in_process', 'completed'])
                    ->whereMonth('approved_at', now()->month)
                    ->whereYear('approved_at', now()->year)
                    ->sum('total_amount');

                $percent = round(($monthlySpent / $department->monthly_budget) * 100, 1);
                $remaining = $department->monthly_budget - $monthlySpent;

                $hasContent = true;
                $budgetIcon = $percent >= 90 ? '🔴' : ($percent >= 70 ? '🟡' : '🟢');
                $lines[] = "💰 <b>งบประมาณแผนก {$department->name}</b>";
                $lines[] = "   งบเดือนนี้: " . number_format($department->monthly_budget, 2) . " THB";
                $lines[] = "   ใช้ไป: " . number_format($monthlySpent, 2) . " THB";
                $lines[] = "   {$budgetIcon} ใช้ไป {$percent}% | เหลือ " . number_format(max(0, $remaining), 2) . " THB";
                $lines[] = "";
            }
        }

        if (!$hasContent) {
            return null; // Don't send empty briefings
        }

        $lines[] = "💡 พิมพ์ /status เพื่อดูรายละเอียด";

        return implode("\n", $lines);
    }
}
