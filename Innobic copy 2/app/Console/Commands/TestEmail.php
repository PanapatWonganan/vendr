<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {email?}';
    protected $description = 'Test email configuration by sending a test email';

    public function handle()
    {
        $recipient = $this->argument('email') ?? 'panapat.w@apppresso.com';
        
        $this->info('Testing email configuration...');
        $this->info('Sending test email to: ' . $recipient);
        
        try {
            Mail::raw('This is a test email from Innobic System. If you receive this email, your email configuration is working correctly!', function ($message) use ($recipient) {
                $message->to($recipient)
                        ->subject('Test Email - Innobic System');
            });
            
            $this->info('✅ Test email sent successfully!');
            $this->info('Please check the inbox for: ' . $recipient);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email!');
            $this->error('Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}