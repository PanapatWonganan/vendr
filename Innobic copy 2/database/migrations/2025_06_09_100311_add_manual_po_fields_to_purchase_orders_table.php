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
            // Add fields for manual PO
            $table->string('po_title')->nullable()->after('po_number');
            $table->string('vendor_name')->nullable()->after('supplier_id');
            $table->string('vendor_contact')->nullable()->after('vendor_name');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('notes');
            $table->text('description')->nullable()->after('po_title');
            
            // Make supplier_id nullable for manual PO
            $table->foreignId('supplier_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'po_title',
                'vendor_name', 
                'vendor_contact',
                'priority',
                'description'
            ]);
            
            // Restore supplier_id constraint
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }
};
