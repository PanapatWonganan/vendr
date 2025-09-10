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
            $table->decimal('procurement_budget', 15, 2)->nullable()->after('procurement_method')->comment('วงเงินในการจัดหา');
            $table->text('delivery_schedule')->nullable()->after('procurement_budget')->comment('งวดการส่งมอบ');
            $table->text('payment_schedule')->nullable()->after('delivery_schedule')->comment('งวดการจ่ายเงิน');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropColumn(['procurement_budget', 'delivery_schedule', 'payment_schedule']);
        });
    }
};
