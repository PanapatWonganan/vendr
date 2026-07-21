<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * จำนวนเงินตรวจรับงวดนี้ที่ผู้ใช้กรอกเอง (override)
     * null = ไม่ได้กรอก → ระบบคำนวณจากมูลค่าสัญญา × เปอร์เซ็นต์ตามเดิม
     */
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('goods_receipts', 'milestone_amount')) {
                $table->decimal('milestone_amount', 15, 2)->nullable()
                    ->after('milestone_percentage')
                    ->comment('จำนวนเงินตรวจรับงวดนี้ (กรอกเอง)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'milestone_amount')) {
                $table->dropColumn('milestone_amount');
            }
        });
    }
};
