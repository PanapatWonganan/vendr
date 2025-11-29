<?php

/**
 * Script to update company database connections
 * Run: php update_company_connections.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Updating Company Database Connections ===\n\n";

use App\Models\Company;

// Update company database connections
$updates = [
    1 => 'innobic_asia',
    2 => 'innobic_nutrition',
    3 => 'innobic_ll',
];

foreach ($updates as $companyId => $connection) {
    $company = Company::find($companyId);

    if ($company) {
        $company->database_connection = $connection;
        $company->save();
        echo "✓ Updated Company ID {$companyId} ({$company->display_name}) to use connection: {$connection}\n";
    } else {
        echo "✗ Company ID {$companyId} not found\n";
    }
}

echo "\n=== Verification ===\n";
$companies = Company::all();
foreach ($companies as $company) {
    echo "Company: {$company->display_name}\n";
    echo "  - ID: {$company->id}\n";
    echo "  - Connection: {$company->database_connection}\n";
    echo "  - Active: " . ($company->is_active ? 'Yes' : 'No') . "\n\n";
}

echo "=== Update Complete! ===\n";
echo "Next steps:\n";
echo "1. Test switching companies in the application\n";
echo "2. Verify each company connects to its own database\n";
echo "3. Create some test data for each company\n";
