<?php
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Mail;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    Mail::raw('Test email from Laravel - PR system', function($message) {
        $message->to('abadoned@gmail.com')
                ->from('panapat.w@apppresso.com', 'Innobic System')
                ->subject('Test Email - PR System');
    });
    
    echo "Email sent successfully!\n";
} catch (Exception $e) {
    echo "Email failed: " . $e->getMessage() . "\n";
}