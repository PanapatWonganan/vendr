<?php

namespace App\Listeners;

use App\Events\PaymentMilestonePaid;
use App\Mail\PaymentMilestoneNotificationMail;
use App\Models\PaymentMilestone;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentMilestoneNotification
{

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
    public function handle(PaymentMilestonePaid $event): void
    {
        try {
            Log::info("💰 PAYMENT EMAIL HANDLER STARTED", [
                'milestone_id' => $event->paymentMilestoneId, 
                'payer_id' => $event->payerUserId
            ]);
            
            // Get payment milestone with relationships
            $paymentMilestone = PaymentMilestone::with([
                'purchaseOrder.vendor', 
                'purchaseOrder.inspectionCommittee',
                'purchaseOrder.purchaseRequisition'
            ])->find($event->paymentMilestoneId);

            if (!$paymentMilestone) {
                Log::warning("PaymentMilestone not found: {$event->paymentMilestoneId}");
                return;
            }

            // Get payer
            $payer = User::find($event->payerUserId);
            if (!$payer) {
                Log::warning("Payer not found: {$event->payerUserId}");
                return;
            }
            
            // Get inspection committee from PR first, then fallback to PO
            $inspectionCommittee = null;
            $committeeSource = 'none';
            
            // Try to get committee from PR first
            if ($paymentMilestone->purchaseOrder?->purchaseRequisition && $paymentMilestone->purchaseOrder->purchaseRequisition->inspection_committee_id) {
                $inspectionCommittee = \App\Models\User::find($paymentMilestone->purchaseOrder->purchaseRequisition->inspection_committee_id);
                $committeeSource = 'PR';
            }
            // Fallback to PO's own committee if PR not available
            elseif ($paymentMilestone->purchaseOrder?->inspectionCommittee) {
                $inspectionCommittee = $paymentMilestone->purchaseOrder->inspectionCommittee;
                $committeeSource = 'PO';
            }
            
            Log::info("💳 Payment Details", [
                'milestone_number' => $paymentMilestone->milestone_number,
                'po_number' => $paymentMilestone->purchaseOrder?->po_number,
                'committee_email' => $inspectionCommittee?->email,
                'committee_source' => $committeeSource,
                'payer_email' => $payer->email
            ]);

            // Send email to inspection committee
            if ($inspectionCommittee && $inspectionCommittee->email) {
                try {
                    Mail::to($inspectionCommittee->email)
                        ->send(new PaymentMilestoneNotificationMail($paymentMilestone, $payer));
                        
                    Log::info("💰 Payment notification sent to inspection committee: {$inspectionCommittee->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send payment notification to inspection committee: " . $e->getMessage());
                }
            }

            // Send copy to payer if different email
            if ($payer->email && $payer->email !== $inspectionCommittee?->email) {
                try {
                    Mail::to($payer->email)
                        ->send(new PaymentMilestoneNotificationMail($paymentMilestone, $payer, true));
                        
                    Log::info("💰 Payment notification sent to payer: {$payer->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send payment notification to payer: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Error handling PaymentMilestonePaid event: " . $e->getMessage());
            throw $e; // Re-throw so it can be retried
        }
    }
}