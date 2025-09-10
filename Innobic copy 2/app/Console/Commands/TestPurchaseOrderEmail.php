<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;

class TestPurchaseOrderEmail extends Command
{
    protected $signature = 'test:po-email {type=approved : Email type (approved/rejected)}';
    protected $description = 'Test Purchase Order email notifications';

    public function handle()
    {
        $type = $this->argument('type');
        
        // Get first purchase order or create a dummy one for testing
        $purchaseOrder = PurchaseOrder::first();
        
        if (!$purchaseOrder) {
            $this->error('No purchase order found in database. Please create one first.');
            return 1;
        }
        
        // Get first user as approver/rejector
        $user = User::first();
        if (!$user) {
            $this->error('No user found in database. Please create one first.');
            return 1;
        }
        
        $this->info("Testing {$type} email for PO: " . $purchaseOrder->po_number);
        $this->info("Using user: " . $user->email);
        
        try {
            if ($type === 'approved') {
                event(new PurchaseOrderApproved($purchaseOrder, $user));
                $this->info('Purchase Order Approved email sent successfully!');
            } else {
                event(new PurchaseOrderRejected($purchaseOrder, $user, 'Test rejection reason - Budget constraints'));
                $this->info('Purchase Order Rejected email sent successfully!');
            }
        } catch (\Exception $e) {
            $this->error('Error sending email: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}