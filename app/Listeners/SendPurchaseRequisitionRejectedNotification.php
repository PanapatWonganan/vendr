<?php

namespace App\Listeners;

use App\Events\PurchaseRequisitionRejected;
use App\Mail\PurchaseRequisitionRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPurchaseRequisitionRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PurchaseRequisitionRejected $event): void
    {
        try {
            $purchaseRequisition = $event->purchaseRequisition;
            $rejector = $event->rejector;

            // Send email to the PR creator (requester)
            if ($purchaseRequisition->requester && $purchaseRequisition->requester->email) {
                Mail::to($purchaseRequisition->requester->email)
                    ->send(new PurchaseRequisitionRejectedMail($purchaseRequisition, $rejector, $purchaseRequisition->requester));

                Log::info('Purchase Requisition rejected email sent to requester', [
                    'pr_number' => $purchaseRequisition->pr_number,
                    'recipient' => $purchaseRequisition->requester->email,
                    'rejector' => $rejector->name,
                    'rejection_reason' => $purchaseRequisition->rejection_notes,
                ]);
            }

            // Send notification to department head if different from rejector
            if ($purchaseRequisition->department && 
                $purchaseRequisition->department->head_id && 
                $purchaseRequisition->department->head_id !== $rejector->id) {
                
                $departmentHead = \App\Models\User::find($purchaseRequisition->department->head_id);
                
                if ($departmentHead && $departmentHead->email) {
                    Mail::to($departmentHead->email)
                        ->send(new PurchaseRequisitionRejectedMail($purchaseRequisition, $rejector, $departmentHead));

                    Log::info('Purchase Requisition rejected email sent to department head', [
                        'pr_number' => $purchaseRequisition->pr_number,
                        'recipient' => $departmentHead->email,
                        'rejector' => $rejector->name,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Purchase Requisition rejected email', [
                'pr_number' => $event->purchaseRequisition->pr_number ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseRequisitionRejected $event, \Throwable $exception): void
    {
        Log::error('Purchase Requisition rejected email job failed', [
            'pr_number' => $event->purchaseRequisition->pr_number ?? 'Unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}