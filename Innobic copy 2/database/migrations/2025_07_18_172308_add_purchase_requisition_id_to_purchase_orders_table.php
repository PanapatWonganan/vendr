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
            $table->unsignedBigInteger('purchase_requisition_id')->nullable()->after('id');
            $table->foreign('purchase_requisition_id')->references('id')->on('purchase_requisitions')->onDelete('set null');
            $table->index('purchase_requisition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_requisition_id']);
            $table->dropIndex(['purchase_requisition_id']);
            $table->dropColumn('purchase_requisition_id');
        });
    }
};
