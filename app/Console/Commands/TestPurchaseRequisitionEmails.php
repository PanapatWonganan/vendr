<?php

namespace App\Console\Commands;

use App\Events\PurchaseRequisitionApproved;
use App\Events\PurchaseRequisitionRejected;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Console\Command;

class TestPurchaseRequisitionEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pr:test-email 
                            {--type=approved : Type of email (approved|rejected)}
                            {--user= : User ID to send test email to}
                            {--pr= : PR ID to use for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Purchase Requisition email notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $userId = $this->option('user');
        $prId = $this->option('pr');

        // Validate type
        if (!in_array($type, ['approved', 'rejected'])) {
            $this->error('❌ Invalid type. Use --type=approved or --type=rejected');
            return 1;
        }

        // Get or create test user
        if ($userId) {
            $testUser = User::find($userId);
            if (!$testUser) {
                $this->error('❌ User not found');
                return 1;
            }
        } else {
            $testUser = User::where('email', 'admin@example.com')->first();
            if (!$testUser) {
                $this->error('❌ No test user found. Please specify --user=ID');
                return 1;
            }
        }

        // Get or create test PR
        if ($prId) {
            $pr = PurchaseRequisition::find($prId);
            if (!$pr) {
                $this->error('❌ PR not found');
                return 1;
            }
        } else {
            $pr = PurchaseRequisition::with('requester')->first();
            if (!$pr) {
                $this->error('❌ No PR found in database');
                return 1;
            }
        }

        $this->info("🧪 Testing {$type} email for PR: {$pr->pr_number}");
        $this->info("📧 Sending to: {$pr->requester->email}");
        $this->info("👤 Approver/Rejector: {$testUser->name}");

        try {
            if ($type === 'approved') {
                event(new PurchaseRequisitionApproved($pr, $testUser));
                $this->info("✅ Approval email event dispatched successfully!");
            } elseif ($type === 'rejected') {
                // Update PR with rejection reason first
                $pr->update([
                    'rejection_notes' => 'Testing rejection email notification system',
                    'status' => 'rejected',
                    'rejected_by' => $testUser->id,
                    'rejected_at' => now(),
                ]);
                
                event(new PurchaseRequisitionRejected($pr, $testUser));
                $this->info("✅ Rejection email event dispatched successfully!");
            }

            $this->info("📧 Email should be queued and will be sent in background.");
            $this->info("💡 To process queue: php artisan queue:work");
            $this->info("📋 To check queue: php artisan queue:status");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
