<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SlaTracking;
use App\Models\TermsOfReference;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SlaService
{
    /**
     * SLA Standards (in working days)
     */
    const SLA_STANDARDS = [
        'agreement_price' => 9,
        'special_1' => 9,
        'special_2' => 9,
        'invitation_bid' => 25,
        'open_bid' => 34,
    ];

    /**
     * Calculate working days between two dates (excluding weekends and holidays)
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        if ($startDate->greaterThan($endDate)) {
            return 0;
        }

        $workingDays = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($date->isWeekend()) {
                continue;
            }

            // TODO: Add holiday checking logic here if needed
            // if ($this->isHoliday($date)) {
            //     continue;
            // }

            $workingDays++;
        }

        return $workingDays;
    }

    /**
     * Calculate calendar days between two dates (DATEDIF-style, ตามสูตร Excel ของผู้ใช้)
     */
    public function calculateCalendarDays(Carbon $startDate, Carbon $endDate): int
    {
        if ($startDate->greaterThan($endDate)) {
            return 0;
        }

        return $startDate->startOfDay()->diffInDays($endDate->startOfDay());
    }

    /**
     * Get SLA standard days for procurement method
     */
    public function getSlaStandardDays(?string $procurementMethod): int
    {
        return self::SLA_STANDARDS[$procurementMethod] ?? 9; // Default 9 days
    }

    /**
     * Calculate SLA grade based on percentage
     */
    public function calculateGrade(float $percentage): string
    {
        return match (true) {
            $percentage <= 50 => 'S',
            $percentage <= 70 => 'A',
            $percentage <= 90 => 'B',
            $percentage <= 100 => 'C',
            $percentage <= 120 => 'D',
            default => 'F',
        };
    }

    /**
     * Track PR Submission to Approval (Stage 1)
     */
    public function trackPrSubmissionToApproval(PurchaseRequisition $pr): ?SlaTracking
    {
        if (! $pr->submitted_at || ! $pr->pr_approved_at) {
            return null;
        }

        $startDate = Carbon::parse($pr->submitted_at);
        $endDate = Carbon::parse($pr->pr_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($pr->procurement_method);

        $percentage = $standardDays > 0 ? ($actualDays / $standardDays) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $daysDiff = $actualDays - $standardDays;
        $status = $daysDiff <= 0 ? 'on_time' : 'late';

        return SlaTracking::updateOrCreate(
            [
                'purchase_requisition_id' => $pr->id,
                'stage' => 'pr_submission_to_approval',
            ],
            [
                'company_id' => $pr->company_id,
                'procurement_method' => $pr->procurement_method,
                'sla_standard_days' => $standardDays,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_working_days' => $actualDays,
                'sla_percentage' => round($percentage, 2),
                'sla_grade' => $grade,
                'days_difference' => $daysDiff,
                'status' => $status,
            ]
        );
    }

    /**
     * Track TOR Submission to Approval
     */
    public function trackTorSubmissionToApproval(TermsOfReference $tor): ?SlaTracking
    {
        if (! $tor->submitted_at || ! $tor->approved_at) {
            return null;
        }

        $startDate = Carbon::parse($tor->submitted_at);
        $endDate = Carbon::parse($tor->approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($tor->procurement_method);

        $percentage = $standardDays > 0 ? ($actualDays / $standardDays) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $daysDiff = $actualDays - $standardDays;
        $status = $daysDiff <= 0 ? 'on_time' : 'late';

        return SlaTracking::updateOrCreate(
            [
                'tor_id' => $tor->id,
                'stage' => 'tor_submission_to_approval',
            ],
            [
                'company_id' => $tor->company_id,
                'procurement_method' => $tor->procurement_method,
                'sla_standard_days' => $standardDays,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_working_days' => $actualDays,
                'sla_percentage' => round($percentage, 2),
                'sla_grade' => $grade,
                'days_difference' => $daysDiff,
                'status' => $status,
            ]
        );
    }

    /**
     * Track PO Creation to Approval (Stage 3)
     */
    public function trackPoCreationToApproval(PurchaseOrder $po): ?SlaTracking
    {
        if (! $po->po_created_at || ! $po->po_approved_at) {
            return null;
        }

        $pr = $po->purchaseRequisition;
        $startDate = Carbon::parse($po->po_created_at);
        $endDate = Carbon::parse($po->po_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($po->procurement_method);

        $percentage = $standardDays > 0 ? ($actualDays / $standardDays) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $daysDiff = $actualDays - $standardDays;
        $status = $daysDiff <= 0 ? 'on_time' : 'late';

        return SlaTracking::updateOrCreate(
            [
                'purchase_order_id' => $po->id,
                'stage' => 'po_creation_to_approval',
            ],
            [
                'company_id' => $po->company_id,
                'purchase_requisition_id' => $pr?->id,
                'procurement_method' => $po->procurement_method,
                'sla_standard_days' => $standardDays,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_working_days' => $actualDays,
                'sla_percentage' => round($percentage, 2),
                'sla_grade' => $grade,
                'days_difference' => $daysDiff,
                'status' => $status,
            ]
        );
    }

    /**
     * Track Full Cycle (PR Created to PO Approved)
     */
    public function trackFullCycle(PurchaseOrder $po): ?SlaTracking
    {
        $pr = $po->purchaseRequisition;

        if (! $pr || ! $pr->submitted_at || ! $po->po_approved_at) {
            return null;
        }

        $startDate = Carbon::parse($pr->submitted_at);
        $endDate = Carbon::parse($po->po_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($po->procurement_method);

        $percentage = $standardDays > 0 ? ($actualDays / $standardDays) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $daysDiff = $actualDays - $standardDays;
        $status = $daysDiff <= 0 ? 'on_time' : 'late';

        return SlaTracking::updateOrCreate(
            [
                'purchase_requisition_id' => $pr->id,
                'purchase_order_id' => $po->id,
                'stage' => 'full_cycle',
            ],
            [
                'company_id' => $po->company_id,
                'procurement_method' => $po->procurement_method,
                'sla_standard_days' => $standardDays,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_working_days' => $actualDays,
                'sla_percentage' => round($percentage, 2),
                'sla_grade' => $grade,
                'days_difference' => $daysDiff,
                'status' => $status,
            ]
        );
    }

    /**
     * Track รับเรื่อง → PO Approved (สูตร Excel ของผู้ใช้)
     *
     * - นับวันปฏิทิน (DATEDIF) ไม่หักวันหยุด ให้ตัวเลขตรงกับชีทที่ผู้ใช้คุ้น
     * - จุดเริ่ม: received_date (วันที่รับเรื่อง) ถ้าไม่มีใช้ submitted_at
     *   (ข้อมูล import เก็บวันที่รับเรื่องไว้ใน submitted_at)
     * - เก็บ %saving จากการต่อรอง: วงเงิน PR ก่อน VAT เทียบ PO subtotal
     */
    public function trackReceivedToPoApproval(PurchaseOrder $po): ?SlaTracking
    {
        $pr = $po->purchaseRequisition;
        $endDate = $po->po_approved_at ?? $po->approved_at;

        if (! $pr || ! $endDate) {
            return null;
        }

        $receivedDate = $pr->received_date ?? $pr->submitted_at;
        if (! $receivedDate) {
            return null;
        }

        $startDate = Carbon::parse($receivedDate);
        $endDate = Carbon::parse($endDate);
        $actualDays = $this->calculateCalendarDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($po->procurement_method ?? $pr->procurement_method);

        $percentage = $standardDays > 0 ? ($actualDays / $standardDays) * 100 : 0;
        $grade = $this->calculateGrade($percentage);
        $daysDiff = $actualDays - $standardDays;
        $status = $daysDiff <= 0 ? 'on_time' : 'late';

        // %saving: วงเงินก่อน VAT (PR) - ราคาที่ต่อได้ (PO subtotal ก่อน VAT)
        $budgetAmount = (float) ($pr->total_amount ?: $pr->procurement_budget ?: 0);
        $finalAmount = (float) ($po->subtotal ?: $po->total_amount ?: 0);
        $savingAmount = $budgetAmount > 0 ? $budgetAmount - $finalAmount : null;
        $savingPercentage = $budgetAmount > 0 ? ($savingAmount / $budgetAmount) * 100 : null;

        return SlaTracking::updateOrCreate(
            [
                'purchase_order_id' => $po->id,
                'stage' => 'received_to_po_approval',
            ],
            [
                'company_id' => $po->company_id,
                'purchase_requisition_id' => $pr->id,
                'procurement_method' => $po->procurement_method ?? $pr->procurement_method,
                'sla_standard_days' => $standardDays,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'actual_working_days' => $actualDays,
                'sla_percentage' => round($percentage, 2),
                'sla_grade' => $grade,
                'days_difference' => $daysDiff,
                'status' => $status,
                'budget_amount' => $budgetAmount ?: null,
                'final_amount' => $finalAmount ?: null,
                'saving_amount' => $savingAmount !== null ? round($savingAmount, 2) : null,
                'saving_percentage' => $savingPercentage !== null ? round($savingPercentage, 2) : null,
            ]
        );
    }

    /**
     * Get SLA statistics for dashboard
     */
    public function getStatistics(?int $companyId = null, ?int $year = null): array
    {
        $query = SlaTracking::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($year) {
            // กรองตามปีของช่วง SLA จริง (start_date) ไม่ใช่วันที่สร้าง record
            $query->whereRaw('YEAR(COALESCE(start_date, created_at)) = ?', [$year]);
        }

        $totalOrders = $query->count();
        $onTimeCount = $query->where('status', 'on_time')->count();
        $onTimeRate = $totalOrders > 0 ? round(($onTimeCount / $totalOrders) * 100, 2) : 0;

        $avgPercentage = $query->avg('sla_percentage') ?? 0;
        $overallGrade = $this->calculateGrade($avgPercentage);

        $gradeDistribution = $query->selectRaw('sla_grade, COUNT(*) as count')
            ->groupBy('sla_grade')
            ->pluck('count', 'sla_grade')
            ->toArray();

        return [
            'total_orders' => $totalOrders,
            'on_time_count' => $onTimeCount,
            'on_time_rate' => $onTimeRate,
            'avg_percentage' => round($avgPercentage, 2),
            'overall_grade' => $overallGrade,
            'grade_distribution' => $gradeDistribution,
        ];
    }
}
