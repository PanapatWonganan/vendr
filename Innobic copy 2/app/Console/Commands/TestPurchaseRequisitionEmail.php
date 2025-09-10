<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;

class TestPurchaseRequisitionEmail extends Command
{
    protected $signature = 'test:pr-email {type=approved : Email type (approved/rejected)}';
    protected $description = 'Test Purchase Requisition email notifications';

    public function handle()
    {
        $type = $this->argument('type');
        
        // Get first purchase requisition
        $purchaseRequisition = PurchaseRequisition::first();
        
        if (!$purchaseRequisition) {
            $this->error('No purchase requisition found in database. Please create one first.');
            return 1;
        }
        
        // Get first user as approver/rejector
        $user = User::first();
        if (!$user) {
            $this->error('No user found in database. Please create one first.');
            return 1;
        }
        
        $this->info("Testing {$type} email for PR: " . $purchaseRequisition->pr_number);
        $this->info("Using user: " . $user->email);
        
        try {
            if ($type === 'approved') {
                event(new PurchaseRequisitionApproved($purchaseRequisition, $user));
                $this->info('Purchase Requisition Approved email sent successfully!');
            } else {
                event(new PurchaseRequisitionRejected($purchaseRequisition, $user, 'Test rejection reason - Need more details'));
                $this->info('Purchase Requisition Rejected email sent successfully!');
            }
        } catch (\Exception $e) {
            $this->error('Error sending email: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}