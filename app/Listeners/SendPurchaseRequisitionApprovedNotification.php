<?php

namespace App\Listeners;

use App\Events\PurchaseRequisitionApproved;
use App\Mail\PurchaseRequisitionApprovedMail;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendPurchaseRequisitionApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PurchaseRequisitionApproved $event): void
    {
        try {
            $purchaseRequisition = $event->purchaseRequisition;
            $approver = $event->approver;

            // Create unique event identifier to prevent duplicates (5 minute window)
            $eventKey = "pr_approved_" . $purchaseRequisition->id . '_' . $approver->id;
            
            // Check if this event was already processed recently
            if (Cache::has($eventKey)) {
                Log::warning('PR Approval: Duplicate event prevented', [
                    'pr_id' => $purchaseRequisition->id,
                ]);
                return;
            }
            
            // Mark event as processed (prevent duplicates for 5 minutes)
            Cache::put($eventKey, now()->toDateTimeString(), 300);

            // Send email to the PR creator (requester)
            if ($purchaseRequisition->requester && $purchaseRequisition->requester->email) {
                Mail::to($purchaseRequisition->requester->email)
                    ->send(new PurchaseRequisitionApprovedMail($purchaseRequisition, $approver, $purchaseRequisition->requester));

                Log::info('PR Approval: Email sent to requester', [
                    'pr_number' => $purchaseRequisition->pr_number,
                    'recipient' => $purchaseRequisition->requester->email,
                ]);
            }

            // Send notification to department head if different from approver
            if ($purchaseRequisition->department && 
                $purchaseRequisition->department->head_id && 
                $purchaseRequisition->department->head_id !== $approver->id) {
                
                $departmentHead = \App\Models\User::find($purchaseRequisition->department->head_id);
                
                if ($departmentHead && $departmentHead->email) {
                    Mail::to($departmentHead->email)
                        ->send(new PurchaseRequisitionApprovedMail($purchaseRequisition, $approver, $departmentHead));

                    Log::info('PR Approval: Email sent to department head', [
                        'pr_number' => $purchaseRequisition->pr_number,
                        'recipient' => $departmentHead->email,
                    ]);
                }
            }

            // Send notification to procurement team
            $procurementManagers = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'procurement_manager');
            })->get();
            
            foreach ($procurementManagers as $manager) {
                if ($manager->email && $manager->id !== $approver->id) {
                    Mail::to($manager->email)
                        ->send(new PurchaseRequisitionApprovedMail($purchaseRequisition, $approver, $manager));

                    Log::info('PR Approval: Email sent to procurement manager', [
                        'pr_number' => $purchaseRequisition->pr_number,
                        'recipient' => $manager->email,
                    ]);
                }
            }

            // Send Telegram notifications
            try {
                $telegramBot = app(TelegramBotService::class);
                $telegramBot->notifyPRApproved($purchaseRequisition, $approver);
            } catch (\Exception $e) {
                Log::error('Telegram PR approved notification error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Purchase Requisition approved email', [
                'pr_number' => $event->purchaseRequisition->pr_number ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseRequisitionApproved $event, \Throwable $exception): void
    {
        Log::error('Purchase Requisition approved email job failed', [
            'pr_number' => $event->purchaseRequisition->pr_number ?? 'Unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}