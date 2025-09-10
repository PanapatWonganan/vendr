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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number')->unique(); // GR-YYYYMMDD-XXXX format
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->date('receipt_date');
            $table->string('delivery_note_number')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft', 
                'completed', 
                'returned', 
                'partially_returned', 
                'cancelled'
            ])->default('draft');
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->json('documents')->nullable(); // Store paths to scanned delivery documents
            $table->text('quality_check_notes')->nullable();
            $table->boolean('is_quality_checked')->default(false);
            $table->foreignId('quality_checked_by')->nullable()->constrained('users');
            $table->timestamp('quality_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
