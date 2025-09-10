<?php

namespace App\Listeners;

use App\Events\PurchaseRequisitionSubmitted;
use App\Mail\PurchaseRequisitionSubmittedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPurchaseRequisitionSubmittedNotification
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
    public function handle(PurchaseRequisitionSubmitted $event): void
    {
        try {
            $purchaseRequisition = $event->purchaseRequisition;
            $submittedBy = $event->submittedBy;

            // หาผู้ที่ต้องรับแจ้งเตือน (ผู้อนุมัติ)
            $approvers = collect();

            // 1. Admin users
            $admins = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();
            $approvers = $approvers->merge($admins);

            // 2. Procurement managers
            $procurementManagers = User::whereHas('roles', function($query) {
                $query->where('name', 'procurement_manager');
            })->get();
            $approvers = $approvers->merge($procurementManagers);

            // 3. Department head of the PR's department
            if ($purchaseRequisition->department_id) {
                $departmentHeads = User::whereHas('roles', function($query) {
                    $query->where('name', 'department_head');
                })->where('department_id', $purchaseRequisition->department_id)->get();
                $approvers = $approvers->merge($departmentHeads);
            }

            // 4. Specific PR approver if set
            if ($purchaseRequisition->pr_approver_id) {
                $specificApprover = User::find($purchaseRequisition->pr_approver_id);
                if ($specificApprover) {
                    $approvers = $approvers->merge(collect([$specificApprover]));
                }
            }

            // Remove duplicates and submitter
            $approvers = $approvers->unique('id')->filter(function($user) use ($submittedBy) {
                return $user->id !== $submittedBy->id;
            });

            // Send emails
            foreach ($approvers as $approver) {
                // Check if user wants to receive PR notifications
                if ($approver->pr_notification_enabled ?? true) {
                    Mail::to($approver->email)->send(
                        new PurchaseRequisitionSubmittedMail($purchaseRequisition, $submittedBy, $approver)
                    );
                    
                    Log::info('PR submission notification sent', [
                        'pr_id' => $purchaseRequisition->id,
                        'pr_number' => $purchaseRequisition->pr_number,
                        'recipient' => $approver->email,
                        'submitter' => $submittedBy->email
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending PR submission notification: ' . $e->getMessage(), [
                'pr_id' => $event->purchaseRequisition->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 