<?php

namespace App\Services;

use App\Models\SlaTracking;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
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
        return match(true) {
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
        if (!$pr->submitted_at || !$pr->pr_approved_at) {
            return null;
        }

        $startDate = Carbon::parse($pr->submitted_at);
        $endDate = Carbon::parse($pr->pr_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($pr->procurement_method);

        $percentage = ($actualDays / $standardDays) * 100;
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
     * Track PO Creation to Approval (Stage 3)
     */
    public function trackPoCreationToApproval(PurchaseOrder $po): ?SlaTracking
    {
        if (!$po->po_created_at || !$po->po_approved_at) {
            return null;
        }

        $pr = $po->purchaseRequisition;
        $startDate = Carbon::parse($po->po_created_at);
        $endDate = Carbon::parse($po->po_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($po->procurement_method);

        $percentage = ($actualDays / $standardDays) * 100;
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

        if (!$pr || !$pr->submitted_at || !$po->po_approved_at) {
            return null;
        }

        $startDate = Carbon::parse($pr->submitted_at);
        $endDate = Carbon::parse($po->po_approved_at);
        $actualDays = $this->calculateWorkingDays($startDate, $endDate);
        $standardDays = $this->getSlaStandardDays($po->procurement_method);

        $percentage = ($actualDays / $standardDays) * 100;
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
     * Get SLA statistics for dashboard
     */
    public function getStatistics(?int $companyId = null): array
    {
        $query = SlaTracking::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
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
