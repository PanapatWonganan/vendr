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
        // Update the status enum to include 'pending' and keep existing values
        DB::statement("ALTER TABLE purchase_requisitions MODIFY COLUMN status ENUM('draft','pending','pending_approval','approved','rejected','in_process','completed','cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (but this might cause data loss if 'pending' status exists)
        DB::statement("ALTER TABLE purchase_requisitions MODIFY COLUMN status ENUM('draft','pending_approval','approved','rejected','in_process','completed','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
