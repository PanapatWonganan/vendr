<?php

/**
 * Script to migrate data from single database to multi-database
 * Run: php migrate_data_to_multi_db.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Multi-Database Migration Script ===\n\n";

// Company mapping
$companies = [
    1 => 'innobic_asia',
    2 => 'innobic_nutrition',
    3 => 'innobic_ll',
];

// Tables with company_id that need to be split
$tablesWithCompanyId = [
    'committee_members',
    'contract_approvals',
    'goods_receipts',
    'payment_milestones',
    'procurement_attachments',
    'purchase_orders',
    'purchase_requisitions',
    'sla_trackings',
    'vendor_evaluations',
    'vendor_scores',
    'vendors',
];

// Tables without company_id (shared data - copy to all databases)
$sharedTables = [
    'users',
    'roles',
    'permissions',
    'role_user',
    'permission_role',
    'departments',
    'companies',
    'migrations',
];

echo "Step 1: Copying shared tables to all databases...\n";
foreach ($sharedTables as $table) {
    echo "  - Copying {$table}...\n";

    // Check if table exists
    try {
        $count = DB::connection('mysql')->table($table)->count();
        echo "    Found {$count} records\n";
    } catch (\Exception $e) {
        echo "    Table {$table} not found, skipping\n";
        continue;
    }

    // Get all data
    $data = DB::connection('mysql')->table($table)->get()->toArray();

    if (empty($data)) {
        echo "    No data to copy\n";
        continue;
    }

    // Convert to array
    $data = json_decode(json_encode($data), true);

    // Copy to all company databases
    foreach ($companies as $companyId => $connection) {
        try {
            // Truncate first
            DB::connection($connection)->table($table)->truncate();

            // Insert in chunks
            foreach (array_chunk($data, 100) as $chunk) {
                DB::connection($connection)->table($table)->insert($chunk);
            }

            echo "    ✓ Copied to {$connection}\n";
        } catch (\Exception $e) {
            echo "    ✗ Error copying to {$connection}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nStep 2: Migrating company-specific data...\n";
foreach ($tablesWithCompanyId as $table) {
    echo "  - Migrating {$table}...\n";

    foreach ($companies as $companyId => $connection) {
        try {
            // Get data for this company
            $data = DB::connection('mysql')
                ->table($table)
                ->where('company_id', $companyId)
                ->get()
                ->toArray();

            if (empty($data)) {
                echo "    No data for company {$companyId}\n";
                continue;
            }

            // Convert to array
            $data = json_decode(json_encode($data), true);

            // Truncate first
            DB::connection($connection)->table($table)->truncate();

            // Insert in chunks
            foreach (array_chunk($data, 100) as $chunk) {
                DB::connection($connection)->table($table)->insert($chunk);
            }

            $count = count($data);
            echo "    ✓ Migrated {$count} records to {$connection}\n";

        } catch (\Exception $e) {
            echo "    ✗ Error for {$connection}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nStep 3: Verifying migration...\n";
foreach ($companies as $companyId => $connection) {
    echo "  Database: {$connection}\n";

    foreach ($tablesWithCompanyId as $table) {
        try {
            $count = DB::connection($connection)->table($table)->count();
            echo "    - {$table}: {$count} records\n";
        } catch (\Exception $e) {
            echo "    - {$table}: Error\n";
        }
    }
    echo "\n";
}

echo "\n=== Migration Complete! ===\n";
echo "Next steps:\n";
echo "1. Test switching companies in the application\n";
echo "2. Verify data isolation\n";
echo "3. Update CompanySelector and CompanyController\n";
echo "4. Git commit and push changes\n";
