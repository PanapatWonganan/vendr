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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add company_id for multi-tenancy (only if not exists)
            if (!Schema::hasColumn('purchase_orders', 'company_id')) {
                $table->unsignedBigInteger('company_id')->default(1);
            }
            
            // Add inspection_committee_id from PR (only if not exists)
            if (!Schema::hasColumn('purchase_orders', 'inspection_committee_id')) {
                $table->unsignedBigInteger('inspection_committee_id')->nullable();
            }
            
            // Add pr_id as alias for purchase_requisition_id (only if not exists)
            if (!Schema::hasColumn('purchase_orders', 'pr_id')) {
                $table->unsignedBigInteger('pr_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['inspection_committee_id']);
            $table->dropForeign(['pr_id']);
            $table->dropColumn(['company_id', 'inspection_committee_id', 'pr_id']);
        });
    }
};
