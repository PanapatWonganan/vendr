<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support MODIFY COLUMN — skip for test environments
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Change enum to string to support more category values
        DB::statement("ALTER TABLE purchase_requisitions MODIFY COLUMN category VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE purchase_requisitions MODIFY COLUMN category ENUM('premium_products', 'advertising_services') NULL");
    }
};
