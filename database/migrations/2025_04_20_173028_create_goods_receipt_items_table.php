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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained();
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('received_quantity', 15, 2);
            $table->string('unit_of_measure');
            $table->decimal('accepted_quantity', 15, 2);
            $table->decimal('rejected_quantity', 15, 2)->default(0);
            $table->string('rejection_reason')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('quality_status')->nullable(); // pass, fail, conditional
            $table->string('remarks')->nullable();
            $table->unsignedInteger('line_number');
            $table->string('serial_numbers')->nullable(); // Comma-separated list of serial numbers or JSON
            $table->boolean('requires_inspection')->default(false);
            $table->boolean('is_inspected')->default(false);
            $table->timestamps();
            
            // Ensure line_number is unique within a Goods Receipt
            $table->unique(['goods_receipt_id', 'line_number'], 'gr_item_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
