<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\ProcurementAnomaly;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnomalyDetectionService
{
    protected int $companyId;
    protected array $newAnomalies = [];

    /**
     * Run full anomaly scan for a company
     */
    public function scan(int $companyId): array
    {
        $this->companyId = $companyId;
        $this->newAnomalies = [];

        Log::info("Anomaly scan started", ['company_id' => $companyId]);

        $this->detectPriceAnomalies();
        $this->detectSplitPR();
        $this->detectBudgetOverrun();
        $this->detectVendorConcentration();
        $this->detectApprovalDelays();

        Log::info("Anomaly scan completed", [
            'company_id' => $companyId,
            'new_anomalies' => count($this->newAnomalies),
        ]);

        return $this->newAnomalies;
    }

    /**
     * Run scan for ALL active companies
     */
    public function scanAll(): array
    {
        $results = [];
        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            $results[$company->id] = $this->scan($company->id);
        }

        return $results;
    }

    // =========================================================================
    // 1. PRICE ANOMALY — ราคาผิดปกติ เทียบกับค่าเฉลี่ยสินค้า/บริการเดียวกัน
    // =========================================================================
    protected function detectPriceAnomalies(): void
    {
        // Find PO items where unit_price deviates > 50% from historical average
        // for the same item description/code within this company
        $recentPOs = PurchaseOrder::where('company_id', $this->companyId)
            ->whereIn('status', ['approved', 'sent_to_supplier', 'acknowledged', 'partially_received', 'fully_received', 'closed'])
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['items', 'vendor'])
            ->get();

        foreach ($recentPOs as $po) {
            foreach ($po->items as $item) {
                if (!$item->unit_price || $item->unit_price <= 0) continue;
                if (!$item->description) continue;

                // Find historical average for similar items (same description keyword, last 12 months)
                $keywords = $this->extractKeywords($item->description);
                if (empty($keywords)) continue;

                $query = DB::table('purchase_order_items as poi')
                    ->join('purchase_orders as po2', 'poi.purchase_order_id', '=', 'po2.id')
                    ->where('po2.company_id', $this->companyId)
                    ->where('po2.created_at', '>=', now()->subMonths(12))
                    ->where('poi.id', '!=', $item->id)
                    ->where('poi.unit_price', '>', 0);

                // Match by item_code first, fallback to description keywords
                if ($item->item_code) {
                    $query->where('poi.item_code', $item->item_code);
                } else {
                    foreach ($keywords as $keyword) {
                        $query->where('poi.description', 'like', "%{$keyword}%");
                    }
                }

                $stats = $query->selectRaw('
                    AVG(poi.unit_price) as avg_price,
                    STDDEV(poi.unit_price) as stddev_price,
                    COUNT(*) as sample_count,
                    MIN(poi.unit_price) as min_price,
                    MAX(poi.unit_price) as max_price
                ')->first();

                if (!$stats || $stats->sample_count < 2 || !$stats->avg_price) continue;

                $avgPrice = (float) $stats->avg_price;
                $deviation = (($item->unit_price - $avgPrice) / $avgPrice) * 100;

                // Alert if price deviates more than 50% above average
                if ($deviation > 50) {
                    $severity = $deviation > 100
                        ? ProcurementAnomaly::SEVERITY_CRITICAL
                        : ProcurementAnomaly::SEVERITY_WARNING;

                    $this->createAnomaly([
                        'type' => ProcurementAnomaly::TYPE_PRICE_ANOMALY,
                        'severity' => $severity,
                        'title' => "ราคา {$item->description} สูงกว่าค่าเฉลี่ย " . number_format($deviation, 0) . "%",
                        'description' => sprintf(
                            "PO %s: สินค้า \"%s\" ราคา %s บาท/หน่วย แต่ค่าเฉลี่ย 12 เดือนคือ %s บาท/หน่วย (จาก %d รายการ) สูงกว่า %.0f%%",
                            $po->po_number,
                            $item->description,
                            number_format($item->unit_price, 2),
                            number_format($avgPrice, 2),
                            $stats->sample_count,
                            $deviation
                        ),
                        'details' => [
                            'po_number' => $po->po_number,
                            'item_description' => $item->description,
                            'item_code' => $item->item_code,
                            'current_price' => $item->unit_price,
                            'avg_price' => round($avgPrice, 2),
                            'min_price' => $stats->min_price,
                            'max_price' => $stats->max_price,
                            'sample_count' => $stats->sample_count,
                            'deviation_percent' => round($deviation, 2),
                            'vendor_name' => $po->vendor?->company_name,
                        ],
                        'vendor_id' => $po->vendor_id,
                        'purchase_order_id' => $po->id,
                        'amount' => $item->unit_price,
                        'expected_amount' => round($avgPrice, 2),
                        'deviation_percent' => round($deviation, 2),
                        'fingerprint' => "price_{$po->id}_{$item->id}",
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 2. SPLIT PR — แยก PR เพื่อหลีกเลี่ยงวงเงินจัดซื้อ
    // =========================================================================
    protected function detectSplitPR(): void
    {
        // Find PRs from the same department submitted within 3 days of each other
        // with combined total exceeding procurement method thresholds
        $threshold = 100000; // วิธีตกลงราคาไม่เกิน 100,000

        $recentPRs = PurchaseRequisition::where('company_id', $this->companyId)
            ->whereIn('status', ['pending_approval', 'approved', 'in_process', 'completed'])
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('department_id')
            ->orderBy('department_id')
            ->orderBy('created_at')
            ->get();

        // Group by department
        $byDepartment = $recentPRs->groupBy('department_id');

        foreach ($byDepartment as $deptId => $prs) {
            if ($prs->count() < 2) continue;

            // Sliding window: check clusters of PRs within 3-day windows
            $prList = $prs->values();

            for ($i = 0; $i < $prList->count(); $i++) {
                $cluster = collect([$prList[$i]]);
                $windowEnd = Carbon::parse($prList[$i]->created_at)->addDays(3);

                for ($j = $i + 1; $j < $prList->count(); $j++) {
                    if (Carbon::parse($prList[$j]->created_at)->lte($windowEnd)) {
                        $cluster->push($prList[$j]);
                    } else {
                        break;
                    }
                }

                if ($cluster->count() < 2) continue;

                // Check if all PRs are individually under threshold but combined is over
                $allUnderThreshold = $cluster->every(fn ($pr) => ($pr->total_amount ?? 0) < $threshold);
                $combinedTotal = $cluster->sum('total_amount');

                if ($allUnderThreshold && $combinedTotal >= $threshold) {
                    $dept = Department::find($deptId);
                    $prNumbers = $cluster->pluck('pr_number')->implode(', ');
                    $severity = $combinedTotal > ($threshold * 3)
                        ? ProcurementAnomaly::SEVERITY_CRITICAL
                        : ProcurementAnomaly::SEVERITY_WARNING;

                    $fingerprint = "split_" . $deptId . "_" . $cluster->pluck('id')->sort()->implode('_');

                    $this->createAnomaly([
                        'type' => ProcurementAnomaly::TYPE_SPLIT_PR,
                        'severity' => $severity,
                        'title' => "พบ PR {$cluster->count()} ใบจากฝ่ายเดียวกัน รวม " . number_format($combinedTotal, 0) . " บาท",
                        'description' => sprintf(
                            "ฝ่าย%s ส่ง PR %d ใบภายใน 3 วัน (%s) แต่ละใบไม่เกิน %s บาท แต่รวมกัน %s บาท อาจเป็นการแยกเพื่อหลีกเลี่ยงวิธีจัดซื้อแบบสอบราคา",
                            $dept?->name ?? 'ID:' . $deptId,
                            $cluster->count(),
                            $prNumbers,
                            number_format($threshold, 0),
                            number_format($combinedTotal, 2)
                        ),
                        'details' => [
                            'department_name' => $dept?->name,
                            'pr_count' => $cluster->count(),
                            'pr_numbers' => $cluster->pluck('pr_number')->toArray(),
                            'pr_ids' => $cluster->pluck('id')->toArray(),
                            'individual_amounts' => $cluster->mapWithKeys(fn ($pr) => [
                                $pr->pr_number => $pr->total_amount,
                            ])->toArray(),
                            'combined_total' => $combinedTotal,
                            'threshold' => $threshold,
                            'window_days' => 3,
                            'date_range' => $cluster->first()->created_at->format('d/m/Y') . ' - ' . $cluster->last()->created_at->format('d/m/Y'),
                        ],
                        'department_id' => $deptId,
                        'amount' => $combinedTotal,
                        'expected_amount' => $threshold,
                        'deviation_percent' => round((($combinedTotal - $threshold) / $threshold) * 100, 2),
                        'fingerprint' => $fingerprint,
                    ]);

                    // Skip to after this cluster to avoid overlapping detections
                    $i += $cluster->count() - 1;
                }
            }
        }
    }

    // =========================================================================
    // 3. BUDGET OVERRUN — งบประมาณเดือนนี้ใกล้หมดหรือเกิน
    // =========================================================================
    protected function detectBudgetOverrun(): void
    {
        $departments = Department::where('is_active', true)
            ->where('monthly_budget', '>', 0)
            ->get();

        $now = Carbon::now();

        foreach ($departments as $dept) {
            // Calculate spending this month (approved PRs)
            $monthlySpend = PurchaseRequisition::where('company_id', $this->companyId)
                ->where('department_id', $dept->id)
                ->whereIn('status', ['approved', 'in_process', 'completed'])
                ->whereMonth('approved_at', $now->month)
                ->whereYear('approved_at', $now->year)
                ->sum('total_amount');

            $budget = (float) $dept->monthly_budget;
            $threshold = $dept->budget_alert_threshold ?? 80;
            $utilization = ($monthlySpend / $budget) * 100;

            if ($utilization >= $threshold) {
                $severity = match (true) {
                    $utilization >= 100 => ProcurementAnomaly::SEVERITY_CRITICAL,
                    $utilization >= 90 => ProcurementAnomaly::SEVERITY_WARNING,
                    default => ProcurementAnomaly::SEVERITY_INFO,
                };

                $remaining = $budget - $monthlySpend;
                $daysLeft = $now->daysUntil($now->copy()->endOfMonth()) + 1;

                $title = $utilization >= 100
                    ? "ฝ่าย{$dept->name} ใช้งบเกิน " . number_format($utilization, 0) . "%"
                    : "ฝ่าย{$dept->name} ใช้งบแล้ว " . number_format($utilization, 0) . "%";

                $this->createAnomaly([
                    'type' => ProcurementAnomaly::TYPE_BUDGET_OVERRUN,
                    'severity' => $severity,
                    'title' => $title,
                    'description' => sprintf(
                        "ฝ่าย%s ใช้งบเดือน %s ไป %s บาท จากงบ %s บาท (%.0f%%) เหลืออีก %s บาท อีก %d วันจะสิ้นเดือน",
                        $dept->name,
                        $now->translatedFormat('F Y'),
                        number_format($monthlySpend, 2),
                        number_format($budget, 2),
                        $utilization,
                        number_format(max(0, $remaining), 2),
                        $daysLeft
                    ),
                    'details' => [
                        'department_name' => $dept->name,
                        'monthly_budget' => $budget,
                        'spent' => round($monthlySpend, 2),
                        'remaining' => round(max(0, $remaining), 2),
                        'utilization_percent' => round($utilization, 2),
                        'threshold_percent' => $threshold,
                        'days_remaining' => $daysLeft,
                        'month' => $now->format('Y-m'),
                        'pr_count' => PurchaseRequisition::where('company_id', $this->companyId)
                            ->where('department_id', $dept->id)
                            ->whereIn('status', ['approved', 'in_process', 'completed'])
                            ->whereMonth('approved_at', $now->month)
                            ->whereYear('approved_at', $now->year)
                            ->count(),
                    ],
                    'department_id' => $dept->id,
                    'amount' => round($monthlySpend, 2),
                    'expected_amount' => $budget,
                    'deviation_percent' => round($utilization - 100, 2),
                    'fingerprint' => "budget_{$dept->id}_{$now->format('Y_m')}",
                ]);
            }
        }
    }

    // =========================================================================
    // 4. VENDOR CONCENTRATION — Vendor กระจุกตัว (1 Vendor > 60% ของ PO ฝ่าย)
    // =========================================================================
    protected function detectVendorConcentration(): void
    {
        // Look at POs in last 6 months, grouped by department + vendor
        $sixMonthsAgo = now()->subMonths(6);

        $deptVendorStats = DB::table('purchase_orders')
            ->where('company_id', $this->companyId)
            ->whereNotNull('vendor_id')
            ->whereNotNull('department_id')
            ->whereIn('status', ['approved', 'sent_to_supplier', 'acknowledged', 'partially_received', 'fully_received', 'closed'])
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('department_id', 'vendor_id')
            ->select([
                'department_id',
                'vendor_id',
                DB::raw('COUNT(*) as po_count'),
                DB::raw('SUM(total_amount) as total_value'),
            ])
            ->get();

        // Group by department to calculate concentration
        $byDept = $deptVendorStats->groupBy('department_id');

        foreach ($byDept as $deptId => $vendorStats) {
            $totalPOs = $vendorStats->sum('po_count');
            $totalValue = $vendorStats->sum('total_value');

            if ($totalPOs < 5) continue; // Need meaningful sample

            foreach ($vendorStats as $stat) {
                $countShare = ($stat->po_count / $totalPOs) * 100;
                $valueShare = $totalValue > 0 ? ($stat->total_value / $totalValue) * 100 : 0;

                // Alert if one vendor has >60% of POs or >70% of value
                if ($countShare > 60 || $valueShare > 70) {
                    $dept = Department::find($deptId);
                    $vendor = \App\Models\Vendor::find($stat->vendor_id);

                    $severity = ($countShare > 80 || $valueShare > 85)
                        ? ProcurementAnomaly::SEVERITY_WARNING
                        : ProcurementAnomaly::SEVERITY_INFO;

                    $this->createAnomaly([
                        'type' => ProcurementAnomaly::TYPE_VENDOR_CONCENTRATION,
                        'severity' => $severity,
                        'title' => sprintf(
                            "%s ได้ PO %.0f%% ของฝ่าย%s",
                            $vendor?->company_name ?? 'Vendor #' . $stat->vendor_id,
                            $countShare,
                            $dept?->name ?? 'ID:' . $deptId
                        ),
                        'description' => sprintf(
                            "ฝ่าย%s: Vendor \"%s\" ได้รับ PO %d จาก %d ใบ (%.0f%%) มูลค่า %s จาก %s บาท (%.0f%%) ใน 6 เดือนที่ผ่านมา ควรกระจายให้ Vendor อื่นเพื่อลดความเสี่ยง",
                            $dept?->name ?? 'ID:' . $deptId,
                            $vendor?->company_name ?? 'N/A',
                            $stat->po_count,
                            $totalPOs,
                            $countShare,
                            number_format($stat->total_value, 2),
                            number_format($totalValue, 2),
                            $valueShare
                        ),
                        'details' => [
                            'department_name' => $dept?->name,
                            'vendor_name' => $vendor?->company_name,
                            'vendor_po_count' => $stat->po_count,
                            'total_po_count' => $totalPOs,
                            'count_share_percent' => round($countShare, 2),
                            'vendor_total_value' => round($stat->total_value, 2),
                            'dept_total_value' => round($totalValue, 2),
                            'value_share_percent' => round($valueShare, 2),
                            'period_months' => 6,
                        ],
                        'department_id' => $deptId,
                        'vendor_id' => $stat->vendor_id,
                        'amount' => round($stat->total_value, 2),
                        'expected_amount' => round($totalValue, 2),
                        'deviation_percent' => round($countShare, 2),
                        'fingerprint' => "vendor_conc_{$deptId}_{$stat->vendor_id}_" . now()->format('Y_m'),
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 5. APPROVAL DELAY — PR ค้างอนุมัตินานเกินไป
    // =========================================================================
    protected function detectApprovalDelays(): void
    {
        // Find PRs pending approval for more than 3 working days
        $delayThresholdDays = 3;

        $stalePRs = PurchaseRequisition::where('company_id', $this->companyId)
            ->where('status', 'pending_approval')
            ->where(function ($q) use ($delayThresholdDays) {
                $q->where('submitted_at', '<=', now()->subDays($delayThresholdDays))
                  ->orWhere(function ($q2) use ($delayThresholdDays) {
                      $q2->whereNull('submitted_at')
                          ->where('updated_at', '<=', now()->subDays($delayThresholdDays));
                  });
            })
            ->with(['requester', 'department'])
            ->get();

        foreach ($stalePRs as $pr) {
            $waitingSince = $pr->submitted_at ?? $pr->updated_at;
            $daysWaiting = (int) Carbon::parse($waitingSince)->diffInDays(now());

            $severity = match (true) {
                $daysWaiting >= 7 => ProcurementAnomaly::SEVERITY_CRITICAL,
                $daysWaiting >= 5 => ProcurementAnomaly::SEVERITY_WARNING,
                default => ProcurementAnomaly::SEVERITY_INFO,
            };

            $this->createAnomaly([
                'type' => ProcurementAnomaly::TYPE_APPROVAL_DELAY,
                'severity' => $severity,
                'title' => "PR {$pr->pr_number} ค้างอนุมัติ {$daysWaiting} วัน",
                'description' => sprintf(
                    "PR \"%s\" (%s) จากฝ่าย%s โดย %s ค้างรออนุมัติมา %d วัน (ส่งวันที่ %s) ระดับความสำคัญ: %s มูลค่า %s บาท",
                    $pr->title ?? '-',
                    $pr->pr_number,
                    $pr->department?->name ?? '-',
                    $pr->requester?->name ?? '-',
                    $daysWaiting,
                    Carbon::parse($waitingSince)->format('d/m/Y'),
                    match ($pr->priority) {
                        'urgent' => 'เร่งด่วน',
                        'high' => 'สูง',
                        'medium' => 'ปานกลาง',
                        default => 'ปกติ',
                    },
                    number_format($pr->total_amount ?? 0, 2)
                ),
                'details' => [
                    'pr_number' => $pr->pr_number,
                    'pr_title' => $pr->title,
                    'requester' => $pr->requester?->name,
                    'department' => $pr->department?->name,
                    'priority' => $pr->priority,
                    'total_amount' => $pr->total_amount,
                    'days_waiting' => $daysWaiting,
                    'submitted_at' => Carbon::parse($waitingSince)->format('Y-m-d H:i'),
                ],
                'department_id' => $pr->department_id,
                'purchase_requisition_id' => $pr->id,
                'amount' => $pr->total_amount,
                'fingerprint' => "delay_{$pr->id}",
            ]);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create an anomaly record, avoiding duplicates by fingerprint
     */
    protected function createAnomaly(array $data): ?ProcurementAnomaly
    {
        $data['company_id'] = $this->companyId;

        // Check for existing open/acknowledged anomaly with same fingerprint
        if (!empty($data['fingerprint'])) {
            $existing = ProcurementAnomaly::where('fingerprint', $data['fingerprint'])
                ->whereIn('status', [ProcurementAnomaly::STATUS_OPEN, ProcurementAnomaly::STATUS_ACKNOWLEDGED])
                ->first();

            if ($existing) {
                // Update severity if it got worse
                if ($this->severityLevel($data['severity'] ?? 'info') > $this->severityLevel($existing->severity)) {
                    $existing->update([
                        'severity' => $data['severity'],
                        'description' => $data['description'],
                        'details' => $data['details'] ?? $existing->details,
                        'amount' => $data['amount'] ?? $existing->amount,
                        'deviation_percent' => $data['deviation_percent'] ?? $existing->deviation_percent,
                    ]);
                }
                return null;
            }
        }

        $anomaly = ProcurementAnomaly::create($data);
        $this->newAnomalies[] = $anomaly;

        return $anomaly;
    }

    /**
     * Convert severity string to numeric level for comparison
     */
    protected function severityLevel(string $severity): int
    {
        return match ($severity) {
            ProcurementAnomaly::SEVERITY_INFO => 1,
            ProcurementAnomaly::SEVERITY_WARNING => 2,
            ProcurementAnomaly::SEVERITY_CRITICAL => 3,
            default => 0,
        };
    }

    /**
     * Extract meaningful keywords from item description for price comparison
     */
    protected function extractKeywords(string $description): array
    {
        // Remove common filler words and split
        $cleaned = preg_replace('/[0-9\.\,\-\/\(\)]+/', ' ', $description);
        $words = array_filter(
            explode(' ', trim($cleaned)),
            fn ($w) => mb_strlen(trim($w)) >= 3
        );

        // Return first 2 meaningful keywords (enough for matching)
        return array_slice(array_values($words), 0, 2);
    }

    /**
     * Get summary statistics of anomalies for a company
     */
    public function getSummary(int $companyId): array
    {
        $base = ProcurementAnomaly::where('company_id', $companyId);

        return [
            'total_open' => (clone $base)->open()->count(),
            'total_unresolved' => (clone $base)->unresolved()->count(),
            'critical_open' => (clone $base)->open()->critical()->count(),
            'by_type' => (clone $base)->unresolved()
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_severity' => (clone $base)->unresolved()
                ->select('severity', DB::raw('COUNT(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'last_scan' => (clone $base)->latest()->value('created_at'),
            'resolved_this_month' => (clone $base)
                ->where('status', ProcurementAnomaly::STATUS_RESOLVED)
                ->whereMonth('resolved_at', now()->month)
                ->count(),
        ];
    }
}
