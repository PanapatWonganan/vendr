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
        Schema::create('contract_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_approval_id')->constrained('contract_approvals')->onDelete('cascade');
            $table->string('file_name'); // ชื่อไฟล์ที่บันทึก
            $table->string('original_name'); // ชื่อไฟล์ต้นฉบับ
            $table->string('file_path'); // path ของไฟล์
            $table->string('file_type'); // MIME type
            $table->unsignedBigInteger('file_size'); // ขนาดไฟล์ (bytes)
            $table->enum('file_category', [
                'contract', // ไฟล์สัญญาหลัก
                'attachment', // เอกสารแนบ
                'amendment', // เอกสารแก้ไขเพิ่มเติม
                'approval', // เอกสารอนุมัติ
                'other' // อื่นๆ
            ])->default('contract');
            $table->text('description')->nullable(); // รายละเอียดไฟล์
            $table->foreignId('uploaded_by')->constrained('users'); // ผู้อัพโหลด
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_files');
    }
};
