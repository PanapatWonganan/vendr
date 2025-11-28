<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Events\PurchaseOrderApproved;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestPOEmail2 extends Command
{
    protected $signature = 'test:po-email2 {po_id}';
    protected $description = 'Test PO approval email without cache';

    public function handle()
    {
        $poId = $this->argument('po_id');
        
        // Clear any existing cache
        Cache::forget("po_approved_{$poId}_1");
        
        $purchaseOrder = PurchaseOrder::find($poId);
        if (!$purchaseOrder) {
            $this->error("PO not found: $poId");
            return 1;
        }
        
        $approver = User::first();
        
        $this->info("Testing PO approval email for: {$purchaseOrder->po_number}");
        $this->info("Vendor email: " . ($purchaseOrder->vendor?->contact_email ?? 'None'));
        
        // Fire the event
        event(new PurchaseOrderApproved($purchaseOrder, $approver));
        
        $this->info("Event fired successfully!");
        
        return 0;
    }
}