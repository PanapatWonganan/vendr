<?php

namespace App\Listeners;

use App\Events\PurchaseOrderAmended;
use App\Models\Company;
use App\Models\PoAmendment;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendPurchaseOrderAmendedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderAmended $event): void
    {
        try {
            // Prevent duplicate notifications within 5-minute window
            $eventKey = "po_amended_{$event->purchaseOrderId}_{$event->amendmentId}";

            if (Cache::has($eventKey)) {
                Log::info('PO Amendment: Duplicate notification skipped', [
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
            $company = Company::find($event->companyId);

            Log::info('PO Amendment notification processing', [
                'po_number' => $purchaseOrder->po_number,
                'amendment_number' => $amendment?->amendment_number,
                'amendment_type' => $amendment?->amendment_type,
                'previous_total' => $amendment?->previous_total_amount,
                'new_total' => $amendment?->new_total_amount,
                'approved_by' => $approver?->name,
            ]);

            // Send Telegram notification to approvers
            $this->sendTelegramNotification($purchaseOrder, $amendment, $approver, $company);

        } catch (\Exception $e) {
            Log::error('PO Amendment notification failed', [
                'po_id' => $event->purchaseOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Telegram notification about PO amendment.
     */
    private function sendTelegramNotification(
        PurchaseOrder $purchaseOrder,
        ?PoAmendment $amendment,
        ?User $approver,
        ?Company $company
    ): void {
        try {
            $telegramBot = app(TelegramBotService::class);

            $companyName = $company?->display_name ?? 'Innobic';
            $previousTotal = $amendment?->previous_total_amount
                ? number_format($amendment->previous_total_amount, 2)
                : '-';
            $newTotal = $amendment?->new_total_amount
                ? number_format($amendment->new_total_amount, 2)
                : '-';

            $message = "📝 *PO Amendment*\n"
                . "บริษัท: {$companyName}\n"
                . "PO: {$purchaseOrder->po_number}\n"
                . "ประเภท: " . ($amendment?->amendment_type ?? '-') . "\n"
                . "มูลค่าเดิม: ฿{$previousTotal}\n"
                . "มูลค่าใหม่: ฿{$newTotal}\n"
                . "อนุมัติโดย: " . ($approver?->name ?? '-');

            $telegramBot->sendToApprovers($message);

            Log::info('PO Amendment: Telegram notification sent', [
                'po_number' => $purchaseOrder->po_number,
            ]);
        } catch (\Exception $e) {
            Log::error('PO Amendment: Telegram notification failed', [
                'po_number' => $purchaseOrder->po_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(PurchaseOrderAmended $event, \Throwable $exception): void
    {
        Log::error('PO amended notification job failed permanently', [
            'po_id' => $event->purchaseOrderId,
            'amendment_id' => $event->amendmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
