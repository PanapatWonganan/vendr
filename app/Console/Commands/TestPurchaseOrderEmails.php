<?php

namespace App\Console\Commands;

use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Console\Command;

class TestPurchaseOrderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:po-emails {po_id} {--type=approved}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Purchase Order email notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $poId = $this->argument('po_id');
        $type = $this->option('type');

        $purchaseOrder = PurchaseOrder::with('creator')->find($poId);
        
        if (!$purchaseOrder) {
            $this->error("Purchase Order with ID {$poId} not found.");
            return 1;
        }

        // Get a random user for testing (preferably admin)
        $testUser = User::where('email', 'admin@example.com')->first() ?? User::first();
        
        if (!$testUser) {
            $this->error("No test user found.");
            return 1;
        }

        $this->info("Testing {$type} email for PO: {$purchaseOrder->po_number}");
        $this->info("PO Creator: {$purchaseOrder->creator->name} ({$purchaseOrder->creator->email})");
        $this->info("Test User: {$testUser->name} ({$testUser->email})");

        try {
            if ($type === 'approved') {
                event(new PurchaseOrderApproved($purchaseOrder, $testUser));
                $this->info("✅ Approval email event dispatched successfully!");
            } elseif ($type === 'rejected') {
                event(new PurchaseOrderRejected($purchaseOrder, $testUser, 'Testing rejection email notification'));
                $this->info("✅ Rejection email event dispatched successfully!");
            } else {
                $this->error("Invalid type. Use --type=approved or --type=rejected");
                return 1;
            }

            $this->info("📧 Email should be queued and will be sent in background.");
            $this->info("💡 Check the mail logs or run: php artisan queue:work");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 