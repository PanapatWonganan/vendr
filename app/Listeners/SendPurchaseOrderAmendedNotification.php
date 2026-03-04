<?php

namespace App\Listeners;

use App\Events\PurchaseOrderAmended;
use App\Models\PoAmendment;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendPurchaseOrderAmendedNotification
{
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderAmended $event): void
    {
        try {
            // Prevent duplicate notifications within 5-minute window
            $eventKey = "po_amended_{$event->purchaseOrderId}_{$event->amendmentId}";

            if (Cache::has($eventKey)) {
                Log::warning('PO Amendment: Duplicate event prevented', [
                    'po_id' => $event->purchaseOrderId,
                    'amendment_id' => $event->amendmentId,
                ]);
                return;
            }

            Cache::put($eventKey, now()->toDateTimeString(), 300);

            $connectionName = $event->connectionName ?? 'mysql';

            // Load PO with connection
            $purchaseOrder = PurchaseOrder::on($connectionName)->find($event->purchaseOrderId);

            if (!$purchaseOrder) {
                Log::error('PO Amendment: PO not found', [
                    'po_id' => $event->purchaseOrderId,
                    'connection' => $connectionName,
                ]);
                return;
            }

            $amendment = PoAmendment::on($connectionName)->find($event->amendmentId);
            $approver = User::find($event->approverId);

            Log::info('PO Amendment notification sent', [
                'po_number' => $purchaseOrder->po_number,
                'amendment_number' => $amendment?->amendment_number,
                'amendment_type' => $amendment?->amendment_type,
                'previous_total' => $amendment?->previous_total_amount,
                'new_total' => $amendment?->new_total_amount,
                'approved_by' => $approver?->name,
            ]);

            // TODO: Add email notification (Mail::to(...)->send(new PurchaseOrderAmendedMail(...)))
            // TODO: Add Telegram notification

        } catch (\Exception $e) {
            Log::error('PO Amendment notification failed', [
                'po_id' => $event->purchaseOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
