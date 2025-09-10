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
            $table->enum('category', ['premium_products', 'advertising_services'])
                  ->nullable()
                  ->comment('หมวดหมู่ใบขอซื้อ: premium_products = สินค้าประเภทของพรี่เมี่ยม, advertising_services = จ้างโฆษณา');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
