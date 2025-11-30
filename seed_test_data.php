<?php

/**
 * Script to create test data for all 3 companies
 * Run: php seed_test_data.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;
use App\Models\PurchaseRequisition;
use Illuminate\Support\Facades\DB;

echo "=== Creating Test Data for All Companies ===\n\n";

// Company configurations
$companies = [
    [
        'id' => 1,
        'name' => 'Innobic Asia',
        'connection' => 'innobic_asia',
    ],
    [
        'id' => 2,
        'name' => 'Innobic Nutrition',
        'connection' => 'innobic_nutrition',
    ],
    [
        'id' => 3,
        'name' => 'Innobic LL',
        'connection' => 'innobic_ll',
    ],
];

foreach ($companies as $company) {
    echo "Creating test data for {$company['name']}...\n";

    try {
        // Create 3 test vendors for each company
        for ($i = 1; $i <= 3; $i++) {
            DB::connection($company['connection'])->table('vendors')->insert([
                'company_id' => $company['id'],
                'vendor_code' => 'V' . $company['id'] . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => $company['name'] . ' - Vendor ' . $i,
                'email' => 'vendor' . $i . '@' . strtolower(str_replace(' ', '', $company['name'])) . '.com',
                'phone' => '02-' . rand(100, 999) . '-' . rand(1000, 9999),
                'address' => rand(1, 999) . ' Test Street, Bangkok',
                'tax_id' => rand(1000000000000, 9999999999999),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        echo "  ✓ Created 3 vendors\n";

        // Create 2 test purchase requisitions for each company
        for ($i = 1; $i <= 2; $i++) {
            DB::connection($company['connection'])->table('purchase_requisitions')->insert([
                'company_id' => $company['id'],
                'pr_number' => 'PR-' . $company['id'] . '-' . date('Y') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'title' => $company['name'] . ' - Test Purchase Requisition ' . $i,
                'description' => 'Test PR for ' . $company['name'],
                'requester_id' => 1, // Assuming user ID 1 exists
                'department_id' => 1, // Assuming department ID 1 exists
                'status' => 'draft',
                'total_amount' => rand(10000, 100000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        echo "  ✓ Created 2 purchase requisitions\n";

    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "=== Verification ===\n\n";

foreach ($companies as $company) {
    echo "Database: {$company['connection']} ({$company['name']})\n";

    try {
        $vendorCount = DB::connection($company['connection'])->table('vendors')->count();
        $prCount = DB::connection($company['connection'])->table('purchase_requisitions')->count();

        echo "  - Vendors: {$vendorCount}\n";
        echo "  - Purchase Requisitions: {$prCount}\n";

        // Show vendor names
        $vendors = DB::connection($company['connection'])->table('vendors')->get(['name']);
        foreach ($vendors as $vendor) {
            echo "    • {$vendor->name}\n";
        }

    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "=== Test Data Creation Complete! ===\n";
echo "\nNow you can:\n";
echo "1. Login to https://innobicprocurement.com/admin\n";
echo "2. Switch between companies\n";
echo "3. View vendors and purchase requisitions for each company\n";
echo "4. Verify data isolation - each company should only see their own data\n";
