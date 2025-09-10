<?php

namespace App\Console\Commands;

use App\Mail\PurchaseOrderApprovedMail;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailTemplate extends Command
{
    protected $signature = 'test:email-template {email?}';
    protected $description = 'Test email templates with sample data';

    public function handle()
    {
        $recipient = $this->argument('email') ?? 'panapat.w@apppresso.com';
        
        $this->info('Testing email templates...');
        
        try {
            $purchaseOrder = PurchaseOrder::with(['purchaseRequisition', 'vendor', 'items'])->first();
            
            if (!$purchaseOrder) {
                $this->warn('No purchase order found in database. Creating sample data for testing...');
                
                $sampleData = (object) [
                    'po_number' => 'PO-TEST-001',
                    'vendor' => (object) ['name' => 'Test Vendor'],
                    'total_amount' => 50000.00,
                    'status' => 'approved',
                    'approved_at' => now(),
                    'requisition' => (object) [
                        'pr_number' => 'PR-TEST-001',
                        'requested_by' => 'Test User'
                    ],
                    'items' => collect([
                        (object) [
                            'description' => 'Test Item 1',
                            'quantity' => 5,
                            'unit_price' => 5000.00,
                            'total' => 25000.00
                        ],
                        (object) [
                            'description' => 'Test Item 2',
                            'quantity' => 10,
                            'unit_price' => 2500.00,
                            'total' => 25000.00
                        ]
                    ])
                ];
                
                $purchaseOrder = $sampleData;
            }
            
            $this->info('Sending Purchase Order Approved email to: ' . $recipient);
            
            $approver = User::first() ?? (object) ['name' => 'Test Approver', 'email' => 'approver@test.com'];
            
            Mail::to($recipient)->send(new PurchaseOrderApprovedMail($purchaseOrder, $approver));
            
            $this->info('✅ Purchase Order Approved email sent successfully!');
            $this->info('Please check the inbox for: ' . $recipient);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send template email!');
            $this->error('Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}