<?php

namespace App\Services;

use App\Models\VendorScore;
use App\Models\VendorEvaluation;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorScoreService
{
    /**
     * Update vendor scores when an evaluation is saved
     */
    public function updateScoresForEvaluation(VendorEvaluation $evaluation)
    {
        if (!$evaluation->vendor_id || !$evaluation->company_id || !$evaluation->overall_score) {
            return;
        }

        DB::transaction(function () use ($evaluation) {
            // Update monthly score
            $this->updatePeriodScore($evaluation, 'month');
            
            // Update quarterly score  
            $this->updatePeriodScore($evaluation, 'quarter');
            
            // Update yearly score
            $this->updatePeriodScore($evaluation, 'year');
        });
    }

    /**
     * Update score for specific period (month, quarter, year)
     */
    protected function updatePeriodScore(VendorEvaluation $evaluation, $period)
    {
        $date = Carbon::parse($evaluation->evaluation_date);
        $year = $date->year;
        $quarter = $period !== 'year' ? $date->quarter : null;
        $month = $period === 'month' ? $date->month : null;

        // Get or create score record
        $score = VendorScore::findOrCreateForPeriod(
            $evaluation->vendor_id,
            $evaluation->company_id,
            $year,
            $quarter,
            $month
        );

        // Store previous score for trend calculation
        $previousScore = $score->weighted_average_score ?? $score->average_score;
        $score->previous_score = $previousScore;

        // Get all evaluations for this period
        $query = VendorEvaluation::where('vendor_id', $evaluation->vendor_id)
            ->where('company_id', $evaluation->company_id)
            ->whereNotNull('overall_score')
            ->whereYear('evaluation_date', $year);

        if ($quarter) {
            $query->whereRaw('QUARTER(evaluation_date) = ?', [$quarter]);
        }
        if ($month) {
            $query->whereMonth('evaluation_date', $month);
        }

        $evaluations = $query->with('purchaseOrder')->get();

        // Calculate scores
        $totalScore = 0;
        $totalWeightedScore = 0;
        $totalPoValue = 0;
        $categoryScores = [];
        $categoryCounts = [];
        $evaluationIds = [];

        foreach ($evaluations as $eval) {
            $evaluationIds[] = $eval->id;
            
            // Get evaluation score (convert from percentage to 4-point scale)
            $evalScore = ($eval->overall_score / 100) * 4;
            $totalScore += $evalScore;
            
            // Get PO value for weighting
            $poValue = 0;
            if ($eval->purchase_order_id && $eval->purchaseOrder) {
                $poValue = $eval->purchaseOrder->total_amount ?? 0;
            }
            $totalPoValue += $poValue;
            $totalWeightedScore += ($evalScore * $poValue);
            
            // Collect category scores
            foreach ($eval->evaluationItems as $item) {
                if ($item->is_applicable && $item->score) {
                    $category = $item->criteria_category;
                    if (!isset($categoryScores[$category])) {
                        $categoryScores[$category] = 0;
                        $categoryCounts[$category] = 0;
                    }
                    $categoryScores[$category] += $item->score;
                    $categoryCounts[$category]++;
                }
            }
        }

        // Calculate averages
        $evaluationCount = $evaluations->count();
        $averageScore = $evaluationCount > 0 ? $totalScore / $evaluationCount : 0;
        $weightedAverageScore = $totalPoValue > 0 ? $totalWeightedScore / $totalPoValue : $averageScore;

        // Calculate category averages
        foreach ($categoryScores as $category => $total) {
            if ($categoryCounts[$category] > 0) {
                $categoryScores[$category] = round($total / $categoryCounts[$category], 2);
            }
        }

        // Update score record
        $score->fill([
            'total_score' => $totalScore,
            'total_weighted_score' => $totalWeightedScore,
            'total_po_value' => $totalPoValue,
            'evaluation_count' => $evaluationCount,
            'average_score' => round($averageScore, 2),
            'weighted_average_score' => round($weightedAverageScore, 2),
            'grade' => VendorScore::calculateGrade($averageScore),
            'weighted_grade' => VendorScore::calculateGrade($weightedAverageScore),
            'category_scores' => $categoryScores,
            'category_counts' => $categoryCounts,
            'last_evaluation_date' => $evaluation->evaluation_date,
            'last_evaluation_id' => $evaluation->id,
            'evaluation_ids' => $evaluationIds,
        ]);

        // Update trend
        $score->updateTrend();
        
        $score->save();

        return $score;
    }

    /**
     * Recalculate all scores for a vendor
     */
    public function recalculateAllScores($vendorId, $companyId)
    {
        $evaluations = VendorEvaluation::where('vendor_id', $vendorId)
            ->where('company_id', $companyId)
            ->whereNotNull('overall_score')
            ->get();

        foreach ($evaluations as $evaluation) {
            $this->updateScoresForEvaluation($evaluation);
        }
    }

    /**
     * Get vendor performance summary
     */
    public function getVendorSummary($vendorId, $companyId)
    {
        $currentYear = Carbon::now()->year;
        $currentQuarter = Carbon::now()->quarter;

        return [
            'current_year' => VendorScore::where('vendor_id', $vendorId)
                ->where('company_id', $companyId)
                ->where('year', $currentYear)
                ->whereNull('quarter')
                ->first(),
            
            'current_quarter' => VendorScore::where('vendor_id', $vendorId)
                ->where('company_id', $companyId)
                ->where('year', $currentYear)
                ->where('quarter', $currentQuarter)
                ->first(),
            
            'quarterly_trend' => VendorScore::where('vendor_id', $vendorId)
                ->where('company_id', $companyId)
                ->whereNotNull('quarter')
                ->orderBy('year', 'desc')
                ->orderBy('quarter', 'desc')
                ->limit(4)
                ->get(),
            
            'yearly_trend' => VendorScore::where('vendor_id', $vendorId)
                ->where('company_id', $companyId)
                ->whereNull('quarter')
                ->orderBy('year', 'desc')
                ->limit(3)
                ->get(),
        ];
    }

    /**
     * Get top performing vendors
     */
    public function getTopPerformers($companyId, $limit = 5)
    {
        $currentYear = Carbon::now()->year;
        
        return VendorScore::with('vendor')
            ->where('company_id', $companyId)
            ->where('year', $currentYear)
            ->whereNull('quarter')
            ->orderBy('weighted_average_score', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get vendors needing improvement
     */
    public function getVendorsNeedingImprovement($companyId)
    {
        $currentYear = Carbon::now()->year;
        
        return VendorScore::with('vendor')
            ->where('company_id', $companyId)
            ->where('year', $currentYear)
            ->whereNull('quarter')
            ->whereIn('weighted_grade', ['C', 'D'])
            ->orderBy('weighted_average_score', 'asc')
            ->get();
    }
}