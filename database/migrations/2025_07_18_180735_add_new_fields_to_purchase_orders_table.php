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
            $table->enum('work_type', ['buy', 'hire', 'rent'])->nullable()->after('po_title');
            $table->string('procurement_method')->nullable()->after('work_type');
            $table->string('company_name')->nullable()->after('procurement_method');
            $table->string('contact_name')->nullable()->after('company_name');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->decimal('stamp_duty', 15, 2)->nullable()->after('total_amount');
            $table->text('delivery_schedule')->nullable()->after('stamp_duty');
            $table->text('payment_schedule')->nullable()->after('delivery_schedule');
            $table->text('operation_duration')->nullable()->after('payment_terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'work_type',
                'procurement_method',
                'company_name',
                'contact_name',
                'contact_email',
                'stamp_duty',
                'delivery_schedule',
                'payment_schedule',
                'operation_duration'
            ]);
        });
    }
};
