<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `purchase_orders.delivery_address` from VARCHAR(191) to TEXT.
 *
 * Reason: real-world delivery addresses (e.g. multi-line warehouse addresses
 * with contact name + landmark + postcode) routinely exceed 191 characters,
 * which caused historical-data import to fail with
 *   SQLSTATE[22001] 1406 Data too long for column 'delivery_address'.
 *
 * Doing this for purchase_requisitions too (delivery_location) for symmetry
 * if it exists with the same VARCHAR(191) constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->change();
        });

        if (Schema::hasColumn('purchase_requisitions', 'delivery_location')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->text('delivery_location')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('delivery_address', 191)->nullable()->change();
        });

        if (Schema::hasColumn('purchase_requisitions', 'delivery_location')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->string('delivery_location', 191)->nullable()->change();
            });
        }
    }
};
