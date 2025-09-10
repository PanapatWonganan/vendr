<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Events\PurchaseOrderApproved;

class TestSingleEmail extends Command
{
    protected $signature = 'test:single-email {po_number}';
    protected $description = 'Test single email notification (no duplicate)';

    public function handle()
    {
        $poNumber = $this->argument('po_number');
        
        $purchaseOrder = PurchaseOrder::where('po_number', $poNumber)->first();
        $approver = User::find(1);
        
        if (!$purchaseOrder || !$approver) {
            $this->error("Purchase Order or User not found!");
            return 1;
        }
        
        $this->info("Firing single PurchaseOrderApproved event...");
        
        // Fire event เพียงครั้งเดียว ไม่ save model
        event(new PurchaseOrderApproved($purchaseOrder, $approver));
        
        $this->info("Event fired once for PO: {$poNumber}");
        
        return 0;
    }
}