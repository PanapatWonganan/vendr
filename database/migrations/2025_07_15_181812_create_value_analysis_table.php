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
        Schema::create('value_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('va_number')->unique(); // VA-YYYYMMDD-XXXX format
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->onDelete('cascade');
            $table->string('work_type'); // ประเภทงาน (จาก PR)
            $table->string('procurement_method')->nullable(); // วิธีจัดหา (จาก PR)
            $table->decimal('total_budget', 15, 2)->nullable(); // งบประมาณรวม
            $table->string('currency', 3)->default('THB');
            
            // Analysis details
            $table->text('analysis_objective')->nullable(); // วัตถุประสงค์การวิเคราะห์
            $table->text('analysis_scope')->nullable(); // ขอบเขตการวิเคราะห์
            $table->json('evaluation_criteria')->nullable(); // เกณฑ์การประเมิน
            $table->json('alternatives')->nullable(); // ทางเลือกต่างๆ
            $table->json('comparison_matrix')->nullable(); // ตารางเปรียบเทียบ
            $table->text('recommendations')->nullable(); // ข้อเสนอแนะ
            $table->text('conclusion')->nullable(); // สรุปผล
            
            // Analysis status
            $table->enum('status', [
                'draft',
                'in_progress', 
                'completed',
                'approved',
                'rejected'
            ])->default('draft');
            
            // User tracking
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('analyzed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('analysis_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('value_analysis');
    }
};
