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
            $table->unsignedBigInteger('value_analysis_id')->nullable()->after('purchase_requisition_id');
            $table->unsignedInteger('amendment_number')->default(0)->after('value_analysis_id');
            $table->timestamp('last_amended_at')->nullable()->after('po_approved_at');

            $table->foreign('value_analysis_id')
                ->references('id')
                ->on('value_analysis')
                ->nullOnDelete();

            $table->index('amendment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['value_analysis_id']);
            $table->dropIndex(['amendment_number']);
            $table->dropColumn(['value_analysis_id', 'amendment_number', 'last_amended_at']);
        });
    }
};
