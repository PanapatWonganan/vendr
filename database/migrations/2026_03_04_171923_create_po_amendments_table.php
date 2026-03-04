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
        Schema::create('po_amendments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('value_analysis_id')->nullable();
            $table->unsignedInteger('amendment_number');
            $table->string('amendment_type');
            $table->text('amendment_reason');
            $table->decimal('previous_total_amount', 15, 2);
            $table->decimal('new_total_amount', 15, 2);
            $table->json('changes_snapshot')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();

            $table->foreign('value_analysis_id')
                ->references('id')
                ->on('value_analysis')
                ->nullOnDelete();

            $table->index(['purchase_order_id', 'amendment_number']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_amendments');
    }
};
