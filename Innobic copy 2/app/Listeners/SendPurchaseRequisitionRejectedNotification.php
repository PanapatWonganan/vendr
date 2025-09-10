<?php

namespace App\Listeners;

use App\Events\PurchaseRequisitionRejected;
use App\Mail\PurchaseRequisitionRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPurchaseRequisitionRejectedNotification
{
    // Removed ShouldQueue to send emails immediately

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
    public function handle(PurchaseRequisitionRejected $event): void
    {
        try {
            $purchaseRequisition = $event->purchaseRequisition;
            $rejectedBy = $event->rejectedBy;
            $rejectionReason = $event->rejectionReason;
            
            // Load requester with email preferences
            $requester = $purchaseRequisition->requester;
            
            if (!$requester) {
                Log::error('PR Rejected: Requester not found', [
                    'pr_id' => $purchaseRequisition->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Check if user wants PR notifications
            if (isset($requester->email_pr_notifications) && !$requester->email_pr_notifications) {
                Log::info('PR Rejected: User disabled PR notifications', [
                    'user_id' => $requester->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Check specifically for rejected notifications
            if (isset($requester->email_pr_rejected) && !$requester->email_pr_rejected) {
                Log::info('PR Rejected: User disabled PR rejected notifications', [
                    'user_id' => $requester->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Send email notification
            Mail::to($requester->email)->send(
                new PurchaseRequisitionRejectedMail($purchaseRequisition, $rejectedBy, $rejectionReason)
            );
            
            Log::info('PR Rejected notification sent', [
                'pr_number' => $purchaseRequisition->pr_number,
                'requester_email' => $requester->email,
                'rejected_by' => $rejectedBy->name,
                'reason' => $rejectionReason
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send PR rejected notification', [
                'pr_id' => $event->purchaseRequisition->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseRequisitionRejected $event, \Exception $exception): void
    {
        Log::error('PR Rejected notification job failed', [
            'pr_id' => $event->purchaseRequisition->id,
            'error' => $exception->getMessage()
        ]);
    }
}
