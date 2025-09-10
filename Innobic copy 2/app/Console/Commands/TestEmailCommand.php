<?php

namespace App\Console\Commands;

use App\Mail\PurchaseRequisitionApprovedMail;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email? : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email system with SendGrid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('Enter email address to test');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address format');
            return 1;
        }

        $this->info('Testing email system...');
        $this->info('Current mailer: ' . config('mail.default'));
        $this->info('From address: ' . config('mail.from.address'));
        $this->info('From name: ' . config('mail.from.name'));

        try {
            // Test simple email
            $this->line('');
            $this->info('🧪 Testing simple email...');
            
            Mail::raw('This is a test email from Innobic system. Email system is working correctly!', function($message) use ($email) {
                $message->to($email)
                        ->subject('✅ Innobic Email Test - ' . now()->format('Y-m-d H:i:s'))
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info('✅ Simple email sent successfully!');

            // Test with PR notification if data exists
            $pr = PurchaseRequisition::with(['requester', 'approvedBy'])->first();
            $approver = User::first();

            if ($pr && $approver) {
                $this->line('');
                $this->info('🧪 Testing PR notification email...');
                
                // Create a test PR email
                $testPR = new PurchaseRequisition([
                    'pr_number' => 'TEST-' . now()->format('Ymd-His'),
                    'title' => 'Test Purchase Requisition for Email',
                    'total_amount' => 1500.00,
                    'currency' => 'THB',
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                
                // Set relations without saving
                $testPR->setRelation('requester', $pr->requester ?? $approver);
                $testPR->setRelation('approvedBy', $approver);

                Mail::to($email)->send(new PurchaseRequisitionApprovedMail($testPR, $approver));
                
                $this->info('✅ PR notification email sent successfully!');
            }

            $this->line('');
            $this->info('🎉 All tests passed! Email system is working correctly.');
            $this->info('💡 Check your inbox (and spam folder) for test emails.');

        } catch (\Exception $e) {
            $this->error('❌ Email test failed: ' . $e->getMessage());
            $this->line('');
            $this->warn('📋 Troubleshooting checklist:');
            $this->line('• Check your .env MAIL_* settings');
            $this->line('• Verify SendGrid API key is correct');
            $this->line('• Ensure sender email is verified in SendGrid');
            $this->line('• Check SendGrid activity dashboard');
            
            return 1;
        }

        return 0;
    }
}
