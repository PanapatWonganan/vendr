<?php

namespace App\Observers;

use App\Models\PurchaseRequisition;
use App\Services\SlaService;
use Carbon\Carbon;

class PurchaseRequisitionObserver
{
    protected $slaService;

    public function __construct(SlaService $slaService)
    {
        $this->slaService = $slaService;
    }

    /**
     * Handle the PurchaseRequisition "updating" event.
     */
    public function updating(PurchaseRequisition $pr): void
    {
        // Track when PR is submitted (status changes to 'submitted' or 'pending_approval')
        if ($pr->isDirty('status')) {
            $newStatus = $pr->status;
            $oldStatus = $pr->getOriginal('status');

            // When PR is submitted for the first time
            if (in_array($newStatus, ['submitted', 'pending_approval']) &&
                !$pr->submitted_at) {
                $pr->submitted_at = now();
            }

            // When PR is approved
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                $pr->pr_approved_at = now();
            }
        }
    }

    /**
     * Handle the PurchaseRequisition "updated" event.
     */
    public function updated(PurchaseRequisition $pr): void
    {
        // After PR is approved, calculate SLA for Stage 1
        if ($pr->status === 'approved' && $pr->pr_approved_at && $pr->submitted_at) {
            try {
                $this->slaService->trackPrSubmissionToApproval($pr);
            } catch (\Exception $e) {
                \Log::error('SLA Tracking Error (PR Approval): ' . $e->getMessage());
            }
        }
    }
}
