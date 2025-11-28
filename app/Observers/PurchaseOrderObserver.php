<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Services\SlaService;

class PurchaseOrderObserver
{
    protected $slaService;

    public function __construct(SlaService $slaService)
    {
        $this->slaService = $slaService;
    }

    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $po): void
    {
        // Set PO created timestamp
        if (!$po->po_created_at) {
            $po->po_created_at = now();
            $po->saveQuietly(); // Save without triggering events
        }
    }

    /**
     * Handle the PurchaseOrder "updating" event.
     */
    public function updating(PurchaseOrder $po): void
    {
        // Track when PO is approved
        if ($po->isDirty('status')) {
            $newStatus = $po->status;
            $oldStatus = $po->getOriginal('status');

            // When PO is approved
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                $po->po_approved_at = now();
            }
        }
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $po): void
    {
        // After PO is approved, calculate SLA
        if ($po->status === 'approved' && $po->po_approved_at) {
            try {
                // Track Stage 3: PO Creation → Approval
                if ($po->po_created_at) {
                    $this->slaService->trackPoCreationToApproval($po);
                }

                // Track Full Cycle: PR Created → PO Approved
                if ($po->purchaseRequisition) {
                    $this->slaService->trackFullCycle($po);
                }
            } catch (\Exception $e) {
                \Log::error('SLA Tracking Error (PO Approval): ' . $e->getMessage());
            }
        }
    }
}
