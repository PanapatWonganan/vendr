<?php

namespace App\Listeners;

use App\Events\PurchaseOrderRejected;
use App\Mail\PurchaseOrderRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPurchaseOrderRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderRejected $event): void
    {
        try {
            $purchaseOrder = $event->purchaseOrder;
            $rejector = $event->rejector;

            // Send email to the PO creator
            if ($purchaseOrder->creator && $purchaseOrder->creator->email) {
                Mail::to($purchaseOrder->creator->email)
                    ->send(new PurchaseOrderRejectedMail($purchaseOrder, $rejector, $purchaseOrder->creator));

                Log::info('Purchase Order rejected email sent to creator', [
                    'po_number' => $purchaseOrder->po_number,
                    'recipient' => $purchaseOrder->creator->email,
                    'rejector' => $rejector->name,
                    'rejection_reason' => $purchaseOrder->rejection_notes,
                ]);
            }

            // Send notification to vendor
            $vendorEmail = $purchaseOrder->vendor?->contact_email ?? $purchaseOrder->vendor_contact;
            
            if ($vendorEmail) {
                Mail::to($vendorEmail)
                    ->send(new PurchaseOrderRejectedMail($purchaseOrder, $rejector, null));

                Log::info('Purchase Order rejected email sent to vendor', [
                    'po_number' => $purchaseOrder->po_number,
                    'vendor_email' => $vendorEmail,
                    'rejector' => $rejector->name,
                ]);
            }

            // Send notification to inspection committee if exists
            if ($purchaseOrder->inspectionCommittee && $purchaseOrder->inspectionCommittee->email) {
                Mail::to($purchaseOrder->inspectionCommittee->email)
                    ->send(new PurchaseOrderRejectedMail($purchaseOrder, $rejector, $purchaseOrder->inspectionCommittee));

                Log::info('Purchase Order rejected email sent to inspection committee', [
                    'po_number' => $purchaseOrder->po_number,
                    'committee_email' => $purchaseOrder->inspectionCommittee->email,
                    'rejector' => $rejector->name,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Purchase Order rejected email', [
                'po_number' => $event->purchaseOrder->po_number ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseOrderRejected $event, \Throwable $exception): void
    {
        Log::error('Purchase Order rejected email job failed', [
            'po_number' => $event->purchaseOrder->po_number ?? 'Unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}