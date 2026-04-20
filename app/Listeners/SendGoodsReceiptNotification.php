<?php

namespace App\Listeners;

use App\Events\GoodsReceiptCreated;
use App\Mail\GoodsReceiptNotificationMail;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGoodsReceiptNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(GoodsReceiptCreated $event): void
    {
        try {
            Log::info('GR Notification: Handler started', ['gr_id' => $event->goodsReceiptId]);
            
            // Get GR with relationships
            $goodsReceipt = GoodsReceipt::with([
                'purchaseOrder', 
                'vendor', 
                'inspectionCommittee',
                'createdBy'
            ])->find($event->goodsReceiptId);

            if (!$goodsReceipt) {
                Log::warning("GoodsReceipt not found: {$event->goodsReceiptId}");
                return;
            }

            // Get creator
            $creator = User::find($event->creatorId);
            if (!$creator) {
                Log::warning("Creator not found: {$event->creatorId}");
                return;
            }
            
            Log::info('GR Notification: Details resolved', [
                'gr_number' => $goodsReceipt->gr_number,
                'committee_email' => $goodsReceipt->inspectionCommittee?->email,
            ]);

            // Send email to inspection committee if assigned
            if ($goodsReceipt->inspectionCommittee && $goodsReceipt->inspectionCommittee->email) {
                try {
                    Mail::to($goodsReceipt->inspectionCommittee->email)
                        ->send(new GoodsReceiptNotificationMail($goodsReceipt, $creator));
                        
                    Log::info("GR notification sent to inspection committee: {$goodsReceipt->inspectionCommittee->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send GR notification to inspection committee: " . $e->getMessage());
                }
            }

            // Optionally send to creator as well
            if ($creator->email && $creator->email !== $goodsReceipt->inspectionCommittee?->email) {
                try {
                    Mail::to($creator->email)
                        ->send(new GoodsReceiptNotificationMail($goodsReceipt, $creator, true));
                        
                    Log::info("GR notification sent to creator: {$creator->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send GR notification to creator: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Error handling GoodsReceiptCreated event: " . $e->getMessage());
            throw $e; // Re-throw so it can be retried
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(GoodsReceiptCreated $event, \Throwable $exception): void
    {
        Log::error('GR Notification job failed permanently', [
            'gr_id' => $event->goodsReceiptId,
            'error' => $exception->getMessage(),
        ]);
    }
}
