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
        Schema::table('goods_receipts', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['supplier_id']);
            
            // Rename the column
            $table->renameColumn('supplier_id', 'vendor_id');
            
            // Add new foreign key constraint to vendors table
            $table->foreign('vendor_id')->references('id')->on('vendors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            // Drop the vendor foreign key constraint
            $table->dropForeign(['vendor_id']);
            
            // Rename back to supplier_id
            $table->renameColumn('vendor_id', 'supplier_id');
            
            // Add back the supplier foreign key constraint
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }
};