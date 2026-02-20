<?php

namespace App\Console\Commands;

use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;
use App\Models\ProcurementAnomaly;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SlaTracking;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWeeklyDigest extends Command
{
    protected $signature = 'telegram:weekly-digest
                            {--test : Send only to admins for testing}
                            {--user= : Send to specific user ID only}';

    protected $description = 'Send weekly procurement digest via Telegram (role-based content)';

    protected \Carbon\Carbon $weekStart;
    protected \Carbon\Carbon $weekEnd;

    public function handle(TelegramBotService $bot): int
    {
        $this->weekStart = now()->subWeek()->startOfWeek();
        $this->weekEnd = now()->subWeek()->endOfWeek();

        $this->info("Weekly Digest: {$this->weekStart->format('d M')} - {$this->weekEnd->format('d M Y')}");

        // Get recipients
        $query = User::whereNotNull('telegram_chat_id');

        if ($this->option('user')) {
            $query->where('id', $this->option('user'));
        } elseif ($this->option('test')) {
            $query->whereHas('roles', fn($q) => $q->where('name', 'admin'));
        } else {
            // Only procurement roles
            $query->whereHas('roles', fn($q) => $q->whereIn('name', [
                'admin', 'procurement_manager', 'procurement_officer',
            ]));
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No eligible recipients found.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($users as $user) {
            try {
                $isManager = $user->hasAnyRole(['admin', 'procurement_manager']);
                $message = $isManager
                    ? $this->buildManagerDigest($user)
                    : $this->buildOfficerDigest($user);

                if ($message) {
                    $bot->sendMessage($user->telegram_chat_id, $message);
                    $sentCount++;
                    $this->line("  Sent to {$user->name} ({$user->email})");
                }
            } catch (\Exception $e) {
                Log::error("Weekly digest error for user {$user->id}: {$e->getMessage()}");
                $this->error("  Failed: {$user->name} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Weekly digest sent to {$sentCount}/{$users->count()} users.");

        return Command::SUCCESS;
    }

    /**
     * Manager/Admin digest: full company overview
     */
    protected function buildManagerDigest(User $user): string
    {
        $lines = [];
        $lines[] = "📊 <b>Weekly Procurement Digest</b>";
        $lines[] = "📅 {$this->weekStart->format('d M')} - {$this->weekEnd->format('d M Y')}";
        $lines[] = "👤 สวัสดีคุณ {$user->name}";
        $lines[] = "";

        // ── 1. PR Summary ──
        $lines[] = "━━━ 📋 Purchase Requisitions ━━━";

        $prCreated = PurchaseRequisition::whereBetween('created_at', [$this->weekStart, $this->weekEnd])->count();
        $prApproved = PurchaseRequisition::where('status', 'approved')
            ->whereBetween('approved_at', [$this->weekStart, $this->weekEnd])->count();
        $prRejected = PurchaseRequisition::where('status', 'rejected')
            ->whereBetween('rejected_at', [$this->weekStart, $this->weekEnd])->count();
        $prPending = PurchaseRequisition::where('status', 'pending_approval')->count();
        $prTotalApproved = PurchaseRequisition::where('status', 'approved')
            ->whereBetween('approved_at', [$this->weekStart, $this->weekEnd])
            ->sum('total_amount');

        $lines[] = "  สร้างใหม่: {$prCreated} ใบ";
        $lines[] = "  ✅ อนุมัติ: {$prApproved} ใบ (฿" . number_format($prTotalApproved, 0) . ")";
        $lines[] = "  ❌ ปฏิเสธ: {$prRejected} ใบ";
        $lines[] = "  ⏳ คงค้าง: {$prPending} ใบ";
        $lines[] = "";

        // ── 2. PO Summary ──
        $lines[] = "━━━ 📦 Purchase Orders ━━━";

        $poCreated = PurchaseOrder::whereBetween('created_at', [$this->weekStart, $this->weekEnd])->count();
        $poDelivered = PurchaseOrder::whereIn('status', ['partially_received', 'fully_received'])
            ->whereBetween('updated_at', [$this->weekStart, $this->weekEnd])->count();
        $poOverdue = PurchaseOrder::whereNotIn('status', ['fully_received', 'closed', 'cancelled'])
            ->where('expected_delivery_date', '<', now())->count();
        $poTotalValue = PurchaseOrder::whereBetween('created_at', [$this->weekStart, $this->weekEnd])
            ->sum('total_amount');

        $lines[] = "  สร้างใหม่: {$poCreated} ใบ (฿" . number_format($poTotalValue, 0) . ")";
        $lines[] = "  📬 ส่งมอบ: {$poDelivered} ใบ";
        if ($poOverdue > 0) {
            $lines[] = "  🔴 เลยกำหนด: {$poOverdue} ใบ";
        }
        $lines[] = "";

        // ── 3. GR Summary ──
        $grCompleted = GoodsReceipt::where('status', 'completed')
            ->whereBetween('receipt_date', [$this->weekStart, $this->weekEnd])->count();
        $grFailed = GoodsReceipt::where('inspection_status', 'failed')
            ->whereBetween('created_at', [$this->weekStart, $this->weekEnd])->count();

        if ($grCompleted > 0 || $grFailed > 0) {
            $lines[] = "━━━ 📥 Goods Receipts ━━━";
            $lines[] = "  ตรวจรับสำเร็จ: {$grCompleted} ใบ";
            if ($grFailed > 0) {
                $lines[] = "  🔴 ไม่ผ่านตรวจ: {$grFailed} ใบ";
            }
            $lines[] = "";
        }

        // ── 4. Payment Summary ──
        $lines[] = "━━━ 💰 Payments ━━━";

        $paidThisWeek = PaymentMilestone::where('status', 'paid')
            ->whereBetween('paid_date', [$this->weekStart, $this->weekEnd]);
        $paidCount = $paidThisWeek->count();
        $paidTotal = (clone $paidThisWeek)->sum('paid_amount');
        $overduePayments = PaymentMilestone::where('status', 'overdue')->count();
        $dueSoon = PaymentMilestone::whereIn('status', ['pending', 'due'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])->count();

        $lines[] = "  จ่ายสัปดาห์นี้: {$paidCount} รายการ (฿" . number_format($paidTotal, 0) . ")";
        if ($overduePayments > 0) {
            $lines[] = "  🔴 ค้างชำระ: {$overduePayments} รายการ";
        }
        if ($dueSoon > 0) {
            $lines[] = "  ⚠️ ครบกำหนดใน 7 วัน: {$dueSoon} รายการ";
        }
        $lines[] = "";

        // ── 5. Spending Trend ──
        $lines[] = "━━━ 📈 Spending Trend ━━━";

        $thisWeekSpend = PurchaseOrder::whereBetween('created_at', [$this->weekStart, $this->weekEnd])
            ->sum('total_amount');
        $lastWeekSpend = PurchaseOrder::whereBetween('created_at', [
            $this->weekStart->copy()->subWeek(),
            $this->weekEnd->copy()->subWeek(),
        ])->sum('total_amount');

        $lines[] = "  สัปดาห์นี้: ฿" . number_format($thisWeekSpend, 0);
        $lines[] = "  สัปดาห์ก่อน: ฿" . number_format($lastWeekSpend, 0);

        if ($lastWeekSpend > 0) {
            $change = (($thisWeekSpend - $lastWeekSpend) / $lastWeekSpend) * 100;
            $arrow = $change >= 0 ? '📈' : '📉';
            $lines[] = "  {$arrow} เปลี่ยนแปลง: " . ($change >= 0 ? '+' : '') . number_format($change, 1) . "%";
        }
        $lines[] = "";

        // ── 6. Top Spending Departments ──
        $topDepts = PurchaseRequisition::where('status', 'approved')
            ->whereBetween('approved_at', [$this->weekStart, $this->weekEnd])
            ->select('department_id', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('department_id')
            ->with('department')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topDepts->isNotEmpty()) {
            $lines[] = "━━━ 🏢 Top Departments ━━━";
            $rank = 1;
            foreach ($topDepts as $dept) {
                $name = $dept->department->name ?? 'N/A';
                $lines[] = "  {$rank}. {$name}: ฿" . number_format($dept->total, 0) . " ({$dept->count} PR)";
                $rank++;
            }
            $lines[] = "";
        }

        // ── 7. Anomaly Summary ──
        $newAnomalies = ProcurementAnomaly::whereBetween('created_at', [$this->weekStart, $this->weekEnd])->count();
        $criticalOpen = ProcurementAnomaly::where('severity', 'critical')->unresolved()->count();

        if ($newAnomalies > 0 || $criticalOpen > 0) {
            $lines[] = "━━━ 🚨 Anomalies ━━━";
            if ($newAnomalies > 0) {
                $lines[] = "  ตรวจพบใหม่: {$newAnomalies} รายการ";
            }
            if ($criticalOpen > 0) {
                $lines[] = "  🔴 Critical ยังไม่แก้ไข: {$criticalOpen} รายการ";
            }
            $lines[] = "";
        }

        // ── 8. SLA Performance ──
        $slaThisWeek = SlaTracking::whereBetween('created_at', [$this->weekStart, $this->weekEnd]);
        $slaTotal = (clone $slaThisWeek)->count();

        if ($slaTotal > 0) {
            $slaPassed = (clone $slaThisWeek)->whereIn('sla_grade', ['S', 'A', 'B'])->count();
            $passRate = round(($slaPassed / $slaTotal) * 100, 1);
            $avgDays = (clone $slaThisWeek)->avg('actual_working_days');

            $lines[] = "━━━ ⏱ SLA Performance ━━━";
            $lines[] = "  ผ่าน SLA: {$passRate}% ({$slaPassed}/{$slaTotal})";
            $lines[] = "  เฉลี่ย: " . number_format($avgDays, 1) . " วันทำการ";
            $lines[] = "";
        }

        // ── Footer ──
        $lines[] = "💡 ดูรายละเอียดเพิ่มเติม:";
        $lines[] = "/dashboard | /report monthly | /anomaly";

        return implode("\n", $lines);
    }

    /**
     * Officer digest: personal workload + assigned tasks
     */
    protected function buildOfficerDigest(User $user): string
    {
        $lines = [];
        $lines[] = "📊 <b>Weekly Digest - สรุปงานสัปดาห์</b>";
        $lines[] = "📅 {$this->weekStart->format('d M')} - {$this->weekEnd->format('d M Y')}";
        $lines[] = "👤 สวัสดีคุณ {$user->name}";
        $lines[] = "";

        // ── 1. My PR Activity ──
        $lines[] = "━━━ 📋 PR ที่คุณดูแล ━━━";

        $myPrCreated = PurchaseRequisition::where('requester_id', $user->id)
            ->whereBetween('created_at', [$this->weekStart, $this->weekEnd])->count();
        $myPrApproved = PurchaseRequisition::where('requester_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('approved_at', [$this->weekStart, $this->weekEnd])->count();
        $myPrRejected = PurchaseRequisition::where('requester_id', $user->id)
            ->where('status', 'rejected')
            ->whereBetween('rejected_at', [$this->weekStart, $this->weekEnd])->count();
        $myPrPending = PurchaseRequisition::where('requester_id', $user->id)
            ->where('status', 'pending_approval')->count();

        $lines[] = "  สร้างใหม่: {$myPrCreated} ใบ";
        $lines[] = "  ✅ อนุมัติ: {$myPrApproved} ใบ";
        if ($myPrRejected > 0) {
            $lines[] = "  ❌ ปฏิเสธ: {$myPrRejected} ใบ";
        }
        $lines[] = "  ⏳ รออนุมัติ: {$myPrPending} ใบ";
        $lines[] = "";

        // ── 2. PO Tracking ──
        $lines[] = "━━━ 📦 PO ติดตาม ━━━";

        $activePOs = PurchaseOrder::whereNotIn('status', ['closed', 'cancelled'])
            ->whereHas('purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->count();
        $overduePOs = PurchaseOrder::whereNotIn('status', ['fully_received', 'closed', 'cancelled'])
            ->where('expected_delivery_date', '<', now())
            ->whereHas('purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->count();
        $deliveredThisWeek = PurchaseOrder::whereIn('status', ['partially_received', 'fully_received'])
            ->whereBetween('updated_at', [$this->weekStart, $this->weekEnd])
            ->whereHas('purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->count();

        $lines[] = "  PO ที่ยังเปิด: {$activePOs} ใบ";
        if ($deliveredThisWeek > 0) {
            $lines[] = "  📬 ส่งมอบสัปดาห์นี้: {$deliveredThisWeek} ใบ";
        }
        if ($overduePOs > 0) {
            $lines[] = "  🔴 เลยกำหนด: {$overduePOs} ใบ";
        }
        $lines[] = "";

        // ── 3. Upcoming Deadlines ──
        $upcomingDeliveries = PurchaseOrder::whereNotIn('status', ['fully_received', 'closed', 'cancelled'])
            ->whereBetween('expected_delivery_date', [now(), now()->addDays(7)])
            ->whereHas('purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->orderBy('expected_delivery_date')
            ->limit(5)
            ->get();

        if ($upcomingDeliveries->isNotEmpty()) {
            $lines[] = "━━━ 📅 กำหนดส่งมอบใน 7 วัน ━━━";
            foreach ($upcomingDeliveries as $po) {
                $daysLeft = now()->diffInDays($po->expected_delivery_date, false);
                $icon = $daysLeft <= 2 ? '🔴' : '🟡';
                $vendorName = $po->vendor->name ?? 'N/A';
                $lines[] = "  {$icon} {$po->po_number} - {$vendorName}";
                $lines[] = "     กำหนด: {$po->expected_delivery_date->format('d M')} (อีก {$daysLeft} วัน)";
            }
            $lines[] = "";
        }

        // ── 4. Payments Due ──
        $upcomingPayments = PaymentMilestone::whereIn('status', ['pending', 'due'])
            ->whereBetween('due_date', [now(), now()->addDays(14)])
            ->whereHas('purchaseOrder.purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($upcomingPayments->isNotEmpty()) {
            $lines[] = "━━━ 💰 การชำระเงินที่ใกล้ถึง ━━━";
            foreach ($upcomingPayments as $pm) {
                $daysLeft = now()->diffInDays($pm->due_date, false);
                $icon = $daysLeft <= 3 ? '🔴' : '🟡';
                $lines[] = "  {$icon} {$pm->milestone_title}";
                $lines[] = "     ฿" . number_format($pm->amount, 0) . " - ครบ {$pm->due_date->format('d M')}";
            }
            $lines[] = "";
        }

        // ── 5. GR Pending Inspection ──
        $pendingGR = GoodsReceipt::where('inspection_status', 'pending')
            ->whereHas('purchaseOrder.purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
            ->count();

        if ($pendingGR > 0) {
            $lines[] = "━━━ 📥 รอตรวจรับ ━━━";
            $lines[] = "  ⚠️ GR รอตรวจ: {$pendingGR} ใบ";
            $lines[] = "";
        }

        // ── 6. Week Stats ──
        $totalProcessed = $myPrCreated + $myPrApproved + $deliveredThisWeek;
        if ($totalProcessed > 0) {
            $lines[] = "━━━ 🎯 สรุปผลงานสัปดาห์ ━━━";
            $lines[] = "  งานที่จัดการได้: {$totalProcessed} รายการ";

            // Average SLA for this user's PRs
            $avgSla = SlaTracking::whereHas('purchaseRequisition', fn($q) => $q->where('requester_id', $user->id))
                ->whereBetween('created_at', [$this->weekStart, $this->weekEnd])
                ->avg('sla_percentage');

            if ($avgSla) {
                $slaIcon = $avgSla >= 80 ? '🟢' : ($avgSla >= 60 ? '🟡' : '🔴');
                $lines[] = "  {$slaIcon} SLA เฉลี่ย: " . number_format($avgSla, 1) . "%";
            }
            $lines[] = "";
        }

        // ── Footer ──
        $lines[] = "💡 ดูรายละเอียด: /mypr | /calendar | /status";

        return implode("\n", $lines);
    }
}
