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
        Schema::create('contract_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique(); // เลขที่สัญญา
            $table->string('contract_title'); // ชื่อสัญญา
            $table->text('description')->nullable(); // รายละเอียดสัญญา
            $table->string('vendor_name'); // ชื่อผู้ขาย/ผู้รับจ้าง
            $table->decimal('contract_value', 15, 2); // มูลค่าสัญญา
            $table->string('currency', 3)->default('THB'); // สกุลเงิน
            $table->date('contract_date'); // วันที่ทำสัญญา
            $table->date('start_date'); // วันที่เริ่มต้นสัญญา
            $table->date('end_date'); // วันที่สิ้นสุดสัญญา
            $table->enum('contract_type', [
                'purchase', // จัดซื้อ
                'service', // จัดจ้าง
                'rental', // เช่า
                'maintenance', // บำรุงรักษา
                'other' // อื่นๆ
            ])->default('purchase');
            $table->enum('status', [
                'pending', // รอตรวจสอบ
                'under_review', // กำลังตรวจสอบ
                'approved', // อนุมัติ
                'rejected', // ไม่อนุมัติ
                'cancelled' // ยกเลิก
            ])->default('pending');
            $table->foreignId('department_id')->constrained('departments'); // แผนก
            $table->foreignId('uploaded_by')->constrained('users'); // ผู้อัพโหลด
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // ผู้ตรวจสอบ
            $table->timestamp('reviewed_at')->nullable(); // วันที่ตรวจสอบ
            $table->text('review_notes')->nullable(); // หมายเหตุการตรวจสอบ
            $table->text('rejection_reason')->nullable(); // เหตุผลที่ไม่อนุมัติ
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('project_code')->nullable(); // รหัสโครงการ
            $table->string('budget_code')->nullable(); // รหัสงบประมาณ
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_approvals');
    }
};
