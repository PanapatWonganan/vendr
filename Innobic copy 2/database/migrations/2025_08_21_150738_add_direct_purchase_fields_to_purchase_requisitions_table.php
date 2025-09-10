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
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            // PR Type - standard, direct_small (≤10,000), direct_medium (≤100,000)
            $table->enum('pr_type', ['standard', 'direct_small', 'direct_medium'])
                  ->default('standard')
                  ->after('pr_number');
            
            // Flag to indicate if PO is required
            $table->boolean('requires_po')->default(true)->after('pr_type');
            
            // Direct Purchase specific fields (for ≤10,000 and ≤100,000)
            $table->date('approval_request_date')->nullable()->after('request_date');
            $table->integer('clause_number')->nullable()->comment('ข้อ 1-5');
            $table->string('prepared_by_id')->nullable()->comment('ผู้จัดทำคำขอ');
            $table->string('io_number')->nullable()->comment('Internal Order');
            $table->string('cost_center')->nullable();
            $table->unsignedBigInteger('supplier_vendor_id')->nullable()->comment('บริษัทที่จัดหา');
            $table->string('reference_document')->nullable()->comment('เอกสารอ้างอิง');
            
            // Completion fields for direct purchase
            $table->timestamp('completion_date')->nullable();
            $table->text('completion_notes')->nullable();
            
            // Add foreign key for supplier_vendor
            $table->foreign('supplier_vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropForeign(['supplier_vendor_id']);
            $table->dropColumn([
                'pr_type',
                'requires_po',
                'approval_request_date',
                'clause_number',
                'prepared_by_id', 
                'io_number',
                'cost_center',
                'supplier_vendor_id',
                'reference_document',
                'completion_date',
                'completion_notes'
            ]);
        });
    }
};
