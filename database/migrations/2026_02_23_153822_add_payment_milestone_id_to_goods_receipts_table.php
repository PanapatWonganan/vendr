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
            $table->foreignId('payment_milestone_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('payment_milestones')
                ->nullOnDelete();

            $table->unique(
                ['purchase_order_id', 'payment_milestone_id'],
                'gr_po_milestone_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropUnique('gr_po_milestone_unique');
            $table->dropConstrainedForeignId('payment_milestone_id');
        });
    }
};
