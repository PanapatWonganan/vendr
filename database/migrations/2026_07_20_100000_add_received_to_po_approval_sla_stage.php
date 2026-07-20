<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // วันที่ฝ่ายจัดซื้อรับเรื่อง (จุดเริ่มนับ SLA ตามสูตร Excel ของผู้ใช้)
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->date('received_date')->nullable()->after('submitted_at');
        });

        // เพิ่ม stage ใหม่ + tor_submission_to_approval ที่หายไปจาก enum เดิม
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sla_trackings MODIFY COLUMN stage ENUM(
                'pr_submission_to_approval',
                'pr_approval_to_po_creation',
                'po_creation_to_approval',
                'full_cycle',
                'tor_submission_to_approval',
                'received_to_po_approval'
            ) NOT NULL");
        }

        // decimal(5,2) เดิมล้นเมื่อใช้เกิน SLA ~10 เท่า (เคสจริง: 209 วัน = 2322%)
        Schema::table('sla_trackings', function (Blueprint $table) {
            $table->decimal('sla_percentage', 8, 2)->nullable()->change();
        });

        // ข้อมูลเงินสำหรับ %saving (วงเงินก่อน VAT เทียบราคาที่ต่อได้)
        Schema::table('sla_trackings', function (Blueprint $table) {
            $table->decimal('budget_amount', 15, 2)->nullable()->after('days_difference');
            $table->decimal('final_amount', 15, 2)->nullable()->after('budget_amount');
            $table->decimal('saving_amount', 15, 2)->nullable()->after('final_amount');
            $table->decimal('saving_percentage', 8, 2)->nullable()->after('saving_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sla_trackings', function (Blueprint $table) {
            $table->dropColumn(['budget_amount', 'final_amount', 'saving_amount', 'saving_percentage']);
        });

        // ไม่ย่อ sla_percentage กลับเป็น decimal(5,2) — ข้อมูลที่เกิน 999.99 จะพัง

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('sla_trackings')
                ->whereIn('stage', ['tor_submission_to_approval', 'received_to_po_approval'])
                ->delete();

            DB::statement("ALTER TABLE sla_trackings MODIFY COLUMN stage ENUM(
                'pr_submission_to_approval',
                'pr_approval_to_po_creation',
                'po_creation_to_approval',
                'full_cycle'
            ) NOT NULL");
        }

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropColumn('received_date');
        });
    }
};
