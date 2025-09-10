<?php

namespace App\Observers;

use App\Models\VendorEvaluation;
use App\Services\VendorScoreService;

class VendorEvaluationObserver
{
    protected $vendorScoreService;

    public function __construct(VendorScoreService $vendorScoreService)
    {
        $this->vendorScoreService = $vendorScoreService;
    }

    /**
     * Handle the VendorEvaluation "created" event.
     */
    public function created(VendorEvaluation $vendorEvaluation): void
    {
        if ($vendorEvaluation->overall_score) {
            $this->vendorScoreService->updateScoresForEvaluation($vendorEvaluation);
        }
    }

    /**
     * Handle the VendorEvaluation "updated" event.
     */
    public function updated(VendorEvaluation $vendorEvaluation): void
    {
        // Update scores if overall_score changed
        if ($vendorEvaluation->isDirty('overall_score') && $vendorEvaluation->overall_score) {
            $this->vendorScoreService->updateScoresForEvaluation($vendorEvaluation);
        }
        
        // Also update if status changed to approved (might trigger score calculation)
        if ($vendorEvaluation->isDirty('status') && $vendorEvaluation->status === 'approved') {
            if ($vendorEvaluation->overall_score) {
                $this->vendorScoreService->updateScoresForEvaluation($vendorEvaluation);
            }
        }
    }

    /**
     * Handle the VendorEvaluation "deleted" event.
     */
    public function deleted(VendorEvaluation $vendorEvaluation): void
    {
        // Recalculate scores without the deleted evaluation
        if ($vendorEvaluation->vendor_id && $vendorEvaluation->company_id) {
            $this->vendorScoreService->recalculateAllScores(
                $vendorEvaluation->vendor_id,
                $vendorEvaluation->company_id
            );
        }
    }

    /**
     * Handle the VendorEvaluation "restored" event.
     */
    public function restored(VendorEvaluation $vendorEvaluation): void
    {
        if ($vendorEvaluation->overall_score) {
            $this->vendorScoreService->updateScoresForEvaluation($vendorEvaluation);
        }
    }
}