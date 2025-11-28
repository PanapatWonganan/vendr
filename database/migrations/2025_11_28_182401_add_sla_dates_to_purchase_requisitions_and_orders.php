<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add SLA tracking dates to purchase_requisitions
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('pr_approved_at')->nullable()->after('submitted_at');
        });

        // Add SLA tracking dates to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('po_created_at')->nullable()->after('created_at');
            $table->timestamp('po_approved_at')->nullable()->after('po_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'pr_approved_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['po_created_at', 'po_approved_at']);
        });
    }
};
