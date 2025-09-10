<?php

use Illuminate\Support\Facades\Mail;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Send test email
try {
    Mail::raw('This is a test email from Innobic system. If you receive this email, it means the email configuration is working correctly!', function ($message) {
        $message->to('panapat.w@apppresso.com')
                ->subject('Test Email - Innobic System');
    });
    
    echo "Test email sent successfully to panapat.w@apppresso.com\n";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response);