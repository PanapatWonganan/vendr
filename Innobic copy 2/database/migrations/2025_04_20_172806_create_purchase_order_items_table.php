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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_requisition_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2);
            $table->string('unit_of_measure');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', [
                'ordered', 
                'partially_received', 
                'fully_received', 
                'cancelled'
            ])->default('ordered');
            $table->decimal('received_quantity', 15, 2)->default(0);
            $table->decimal('returned_quantity', 15, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->unsignedInteger('line_number');
            $table->timestamps();
            
            // Ensure line_number is unique within a PO
            $table->unique(['purchase_order_id', 'line_number'], 'po_item_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
