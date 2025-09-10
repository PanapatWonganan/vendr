<?php

namespace App\Listeners;

use App\Events\GoodsReceiptCreated;
use App\Mail\GoodsReceiptCreatedMail;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendGoodsReceiptCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(GoodsReceiptCreated $event): void
    {
        // Set the database connection for this listener
        config(['database.default' => $event->connectionName]);
        
        // Switch to the company's database context
        session(['company_id' => $event->companyId]);
        
        try {
            // Get the goods receipt and creator
            $goodsReceipt = GoodsReceipt::with(['purchaseOrder', 'supplier', 'inspectionCommittee'])
                ->find($event->goodsReceiptId);
            $creator = User::find($event->creatorId);

            if (!$goodsReceipt || !$creator) {
                return;
            }

            // Send email to inspection committee if assigned
            if ($goodsReceipt->inspection_committee_id && $goodsReceipt->inspectionCommittee) {
                Mail::to($goodsReceipt->inspectionCommittee->email)->send(
                    new GoodsReceiptCreatedMail(
                        $goodsReceipt,
                        $creator,
                        $goodsReceipt->inspectionCommittee
                    )
                );
            }

            // Also send notification to the creator for confirmation
            Mail::to($creator->email)->send(
                new GoodsReceiptCreatedMail(
                    $goodsReceipt,
                    $creator,
                    null // No inspection committee for creator email
                )
            );

            // Update committee_notified_at timestamp
            $goodsReceipt->update([
                'committee_notified_at' => now()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send goods receipt created notification: ' . $e->getMessage(), [
                'goods_receipt_id' => $event->goodsReceiptId,
                'creator_id' => $event->creatorId,
                'company_id' => $event->companyId,
                'connection' => $event->connectionName,
            ]);
        }
    }
}