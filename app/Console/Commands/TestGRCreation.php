<?php

namespace App\Console\Commands;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class TestGRCreation extends Command
{
    protected $signature = 'test:gr-creation {po_id?}';
    protected $description = 'Test Goods Receipt creation';

    public function handle()
    {
        $poId = $this->argument('po_id') ?? 1;
        
        $this->info("🧪 Testing GR Creation");
        $this->info("Using PO ID: {$poId}");
        
        // Check if PO exists and is approved
        $po = PurchaseOrder::find($poId);
        if (!$po) {
            $this->error("❌ Purchase Order not found with ID: {$poId}");
            return;
        }
        
        if ($po->status !== 'approved') {
            $this->error("❌ Purchase Order {$po->po_number} is not approved (Status: {$po->status})");
            return;
        }
        
        $this->info("✅ Found approved PO: {$po->po_number}");
        
        // Get supplier_id from the PO's vendor or default to 1
        $supplierId = $po->vendor_id ?? 1;
        
        // Test data for GR creation
        $testData = [
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplierId,
            'receipt_date' => now(),
            'delivery_milestone' => 1,
            'milestone_percentage' => 100.00,
            'inspection_status' => 'passed',
            'status' => 'draft',
            'received_by' => 1, // Admin user
            'is_quality_checked' => false,
            'inspection_notes' => 'Test GR creation - all items received and inspected',
            'created_by' => 1, // Admin user
        ];
        
        try {
            // Simulate session company (this would normally come from middleware)
            session(['selected_company_id' => 1]);
            
            $this->info("📝 Creating GR with test data...");
            
            $gr = new GoodsReceipt();
            $gr->fill($testData);
            
            // Generate receipt number
            $gr->gr_number = $gr->generateReceiptNumber();
            $gr->receipt_number = $gr->gr_number; // Use same value for both fields
            
            $this->info("Generated receipt number: {$gr->gr_number}");
            
            $gr->save();
            
            $this->info("✅ Goods Receipt created successfully!");
            $this->info("📋 GR Details:");
            $this->info("   - ID: {$gr->id}");
            $this->info("   - GR Number: {$gr->gr_number}");
            $this->info("   - Receipt Number: {$gr->receipt_number}");
            $this->info("   - PO Number: {$po->po_number}");
            $this->info("   - Supplier ID: {$gr->supplier_id}");
            $this->info("   - Company ID: {$gr->company_id}");
            $this->info("   - Status: {$gr->status}");
            $this->info("   - Received by: {$gr->received_by}");
            $this->info("   - Created by: {$gr->created_by}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to create Goods Receipt:");
            $this->error("Error: " . $e->getMessage());
            
            // Show the full SQL error for debugging
            if (method_exists($e, 'getSql')) {
                $this->error("SQL: " . $e->getSql());
            }
        }
    }
}