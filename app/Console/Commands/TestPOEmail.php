<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Events\PurchaseOrderApproved;
use Illuminate\Console\Command;

class TestPOEmail extends Command
{
    protected $signature = 'test:po-email {po_id} {--email=}';
    protected $description = 'Test PO approval email';

    public function handle()
    {
        $poId = $this->argument('po_id');
        $testEmail = $this->option('email');
        
        $purchaseOrder = PurchaseOrder::find($poId);
        if (!$purchaseOrder) {
            $this->error("PO not found: $poId");
            return 1;
        }
        
        $approver = User::first();
        
        $this->info("Testing PO approval email for: {$purchaseOrder->po_number}");
        $this->info("Approver: {$approver->name}");
        
        if ($testEmail) {
            $this->info("Sending to test email: $testEmail");
            config(['mail.to.address' => $testEmail]);
        }
        
        // Fire the event
        event(new PurchaseOrderApproved($purchaseOrder, $approver));
        
        $this->info("Event fired successfully!");
        
        return 0;
    }
}