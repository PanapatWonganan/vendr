<?php

namespace App\Observers;

use App\Models\GoodsReceipt;
use App\Models\PaymentMilestone;

class GoodsReceiptObserver
{
    /**
     * Handle the GoodsReceipt "updated" event.
     * When GR is completed + inspection passed -> set milestone to "due"
     */
    public function updated(GoodsReceipt $goodsReceipt): void
    {
        if (!$goodsReceipt->payment_milestone_id) {
            return;
        }

        $statusChanged = $goodsReceipt->wasChanged('status');
        $inspectionChanged = $goodsReceipt->wasChanged('inspection_status');

        if (!$statusChanged && !$inspectionChanged) {
            return;
        }

        $milestone = $goodsReceipt->paymentMilestone;
        if (!$milestone) {
            return;
        }

        // GR completed + inspection passed -> milestone becomes "due"
        if (
            $goodsReceipt->status === GoodsReceipt::STATUS_COMPLETED &&
            $goodsReceipt->inspection_status === GoodsReceipt::INSPECTION_PASSED &&
            $milestone->status === PaymentMilestone::STATUS_PENDING
        ) {
            $milestone->update([
                'status' => PaymentMilestone::STATUS_DUE,
                'updated_by' => auth()->id(),
            ]);
        }

        // GR cancelled -> revert milestone back to "pending"
        if (
            $goodsReceipt->status === GoodsReceipt::STATUS_CANCELLED &&
            $milestone->status === PaymentMilestone::STATUS_DUE
        ) {
            $milestone->update([
                'status' => PaymentMilestone::STATUS_PENDING,
                'updated_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the GoodsReceipt "deleted" event.
     * Revert milestone status back to "pending" if it was "due"
     */
    public function deleted(GoodsReceipt $goodsReceipt): void
    {
        if (!$goodsReceipt->payment_milestone_id) {
            return;
        }

        $milestone = $goodsReceipt->paymentMilestone;
        if ($milestone && $milestone->status === PaymentMilestone::STATUS_DUE) {
            $milestone->update([
                'status' => PaymentMilestone::STATUS_PENDING,
                'updated_by' => auth()->id(),
            ]);
        }
    }
}
