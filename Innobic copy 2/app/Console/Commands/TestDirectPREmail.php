<?php

namespace App\Console\Commands;

use App\Events\PurchaseRequisitionApproved;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Console\Command;

class TestDirectPREmail extends Command
{
    protected $signature = 'test:direct-pr-email {email?}';
    protected $description = 'Test direct purchase PR approval email';

    public function handle()
    {
        $recipient = $this->argument('email') ?? 'panapat.w@apppresso.com';
        
        $this->info('Testing Direct Purchase PR approval email...');
        
        try {
            // Find a direct purchase PR or create sample data
            $directPR = PurchaseRequisition::where('pr_type', 'direct_small')->first();
            
            if (!$directPR) {
                $this->warn('No direct purchase PR found. Creating sample...');
                $directPR = (object) [
                    'id' => 999,
                    'pr_number' => 'PR-TEST-DIRECT-001',
                    'pr_type' => 'direct_small',
                    'title' => 'ทดสอบ PR จัดซื้อตรง ≤10,000',
                    'total_amount' => 5000.00,
                    'status' => 'approved',
                    'clause_number' => 1,
                    'io_number' => 'IO-TEST-001',
                    'cost_center' => 'CC-001',
                    'reference_document' => 'REF-001',
                    'requester' => (object) ['name' => 'Test User', 'email' => $recipient],
                ];
            } else {
                // Update email for testing
                $directPR->requester->email = $recipient;
            }
            
            $approver = User::first() ?? (object) ['name' => 'Test Approver', 'email' => 'approver@test.com'];
            
            $this->info("Sending Direct PR approval email to: {$recipient}");
            $this->info("PR Number: {$directPR->pr_number}");
            $this->info("PR Type: Direct Purchase ≤10,000");
            
            // Dispatch the same event as normal approval
            event(new PurchaseRequisitionApproved($directPR, $approver));
            
            $this->info('✅ Direct Purchase PR approval email sent successfully!');
            $this->info('Please check inbox: ' . $recipient);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send Direct PR email!');
            $this->error('Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}