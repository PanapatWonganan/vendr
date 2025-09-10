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
        Schema::table('value_analysis', function (Blueprint $table) {
            $table->text('procured_from')->nullable()->after('procurement_method'); // จัดหาจาก
            $table->decimal('agreed_amount', 15, 2)->nullable()->after('procured_from'); // วงเงินที่ตกลงซื้อหรือจ้าง (บาท)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('value_analysis', function (Blueprint $table) {
            $table->dropColumn(['procured_from', 'agreed_amount']);
        });
    }
};
