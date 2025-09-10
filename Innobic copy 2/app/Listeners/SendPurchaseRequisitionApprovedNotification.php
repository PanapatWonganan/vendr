<?php

namespace App\Listeners;

use App\Events\PurchaseRequisitionApproved;
use App\Mail\PurchaseRequisitionApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SendPurchaseRequisitionApprovedNotification
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
    public function handle(PurchaseRequisitionApproved $event): void
    {
        try {
            // Create unique event identifier to prevent duplicates (5 minute window)
            $eventKey = "pr_approved_" . $event->purchaseRequisition->id . '_' . $event->approver->id;
            
            // Check if this event was already processed recently
            if (Cache::has($eventKey)) {
                Log::warning('🚫 DUPLICATE PR EMAIL PREVENTED', [
                    'event_key' => $eventKey,
                    'pr_id' => $event->purchaseRequisition->id,
                    'cached_at' => Cache::get($eventKey)
                ]);
                return;
            }
            
            // Mark event as processed (prevent duplicates for 5 minutes)
            Cache::put($eventKey, now()->toDateTimeString(), 300);
            
            $purchaseRequisition = $event->purchaseRequisition;
            $approver = $event->approver;
            
            // Load requester with email preferences
            $requester = $purchaseRequisition->requester;
            
            if (!$requester) {
                Log::error('PR Approved: Requester not found', [
                    'pr_id' => $purchaseRequisition->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Check if user wants PR notifications
            if (isset($requester->email_pr_notifications) && !$requester->email_pr_notifications) {
                Log::info('PR Approved: User disabled PR notifications', [
                    'user_id' => $requester->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Check specifically for approved notifications
            if (isset($requester->email_pr_approved) && !$requester->email_pr_approved) {
                Log::info('PR Approved: User disabled PR approved notifications', [
                    'user_id' => $requester->id,
                    'pr_number' => $purchaseRequisition->pr_number
                ]);
                return;
            }
            
            // Send email notification to requester
            Mail::to($requester->email)->send(
                new PurchaseRequisitionApprovedMail($purchaseRequisition, $approver)
            );
            
            Log::info('PR Approved notification sent to requester', [
                'pr_number' => $purchaseRequisition->pr_number,
                'requester_email' => $requester->email,
                'approver' => $approver->name
            ]);

            // For direct purchase PRs, also send email to prepared_by person
            if ($purchaseRequisition->isDirectPurchase() && $purchaseRequisition->prepared_by_id) {
                $preparedBy = $purchaseRequisition->preparedBy;
                
                if ($preparedBy && $preparedBy->email && $preparedBy->email !== $requester->email) {
                    Mail::to($preparedBy->email)->send(
                        new PurchaseRequisitionApprovedMail($purchaseRequisition, $approver)
                    );
                    
                    Log::info('PR Approved notification sent to prepared_by', [
                        'pr_number' => $purchaseRequisition->pr_number,
                        'prepared_by_email' => $preparedBy->email,
                        'approver' => $approver->name
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send PR approved notification', [
                'pr_id' => $event->purchaseRequisition->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseRequisitionApproved $event, \Exception $exception): void
    {
        Log::error('PR Approved notification job failed', [
            'pr_id' => $event->purchaseRequisition->id,
            'error' => $exception->getMessage()
        ]);
    }
}
