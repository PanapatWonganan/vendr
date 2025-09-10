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
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->onDelete('cascade');
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2);
            $table->string('unit_of_measure');
            $table->decimal('estimated_unit_price', 15, 2)->default(0);
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->date('required_date')->nullable();
            $table->string('specification')->nullable();
            $table->enum('status', [
                'pending', 
                'ordered', 
                'partially_ordered', 
                'received', 
                'cancelled'
            ])->default('pending');
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers');
            $table->string('remarks')->nullable();
            $table->string('account_code')->nullable();
            $table->json('technical_attachments')->nullable();
            $table->decimal('ordered_quantity', 15, 2)->default(0);
            $table->decimal('received_quantity', 15, 2)->default(0);
            $table->unsignedInteger('line_number');
            $table->timestamps();
            
            // Ensure line_number is unique within a PR
            $table->unique(['purchase_requisition_id', 'line_number'], 'pr_item_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
    }
};
