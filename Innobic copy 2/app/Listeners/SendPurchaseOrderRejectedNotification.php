<?php

namespace App\Listeners;

use App\Events\PurchaseOrderRejected;
use App\Mail\PurchaseOrderRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPurchaseOrderRejectedNotification
{
    // Removed ShouldQueue to send emails immediately

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderRejected $event): void
    {
        try {
            $purchaseOrder = $event->purchaseOrder;
            $rejector = $event->rejector;
            $rejectionReason = $event->rejectionReason;

            // Load the creator relationship to get the PO requester
            $purchaseOrder->load('creator');
            $creator = $purchaseOrder->creator;

            if ($creator && $creator->email) {
                // Check if user wants to receive rejection emails
                if ($creator->email_po_rejected && $creator->email_po_notifications) {
                    // Send email to the PO creator (requester)
                    Mail::to($creator->email)
                        ->send(new PurchaseOrderRejectedMail($purchaseOrder, $rejector, $creator, $rejectionReason));

                    Log::info('Purchase Order rejected email sent', [
                        'po_number' => $purchaseOrder->po_number,
                        'recipient' => $creator->email,
                        'rejector' => $rejector->name,
                        'reason' => $rejectionReason,
                    ]);
                } else {
                    Log::info('Purchase Order rejected email skipped - user preferences disabled', [
                        'po_number' => $purchaseOrder->po_number,
                        'recipient' => $creator->email,
                        'email_po_rejected' => $creator->email_po_rejected,
                        'email_po_notifications' => $creator->email_po_notifications,
                    ]);
                }
            } else {
                Log::warning('Cannot send rejection email - creator not found or no email', [
                    'po_number' => $purchaseOrder->po_number,
                    'creator_id' => $purchaseOrder->created_by,
                ]);
            }

            // Optional: Send notification to department head or admin if needed
            // You can add more recipients here based on business requirements

        } catch (\Exception $e) {
            Log::error('Failed to send Purchase Order rejected email', [
                'po_number' => $event->purchaseOrder->po_number,
                'error' => $e->getMessage(),
            ]);

            // Don't re-throw the exception to prevent job failure
            // The main rejection process should continue even if email fails
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseOrderRejected $event, \Throwable $exception): void
    {
        Log::error('Purchase Order rejected email job failed', [
            'po_number' => $event->purchaseOrder->po_number,
            'error' => $exception->getMessage(),
        ]);
    }
} 