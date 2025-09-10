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
        Schema::create('vendor_evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_evaluation_id')->constrained('vendor_evaluations')->onDelete('cascade');
            
            // Criteria information
            $table->string('criteria_category'); // เช่น 'quality', 'delivery', 'service'
            $table->string('criteria_name'); // ชื่อหัวข้อการประเมิน
            $table->text('criteria_description')->nullable(); // คำอธิบายหัวข้อ
            
            // Scoring (1-4 or null for N/A)
            $table->tinyInteger('score')->nullable(); // 1=ควรปรับปรุง, 2=พอใช้, 3=ดี, 4=ดีมาก, null=N/A
            $table->boolean('is_applicable')->default(true); // false for N/A items
            
            // Additional feedback
            $table->text('comments')->nullable(); // ความคิดเห็นสำหรับหัวข้อนี้
            $table->text('evidence')->nullable(); // หลักฐานหรือตัวอย่าง
            
            // Weight for scoring (if needed later)
            $table->decimal('weight', 3, 2)->default(1.00); // น้ำหนักของหัวข้อนี้
            
            $table->timestamps();
            
            // Indexes
            $table->index(['vendor_evaluation_id', 'criteria_category'], 'vendor_eval_items_eval_category_idx');
            $table->index('criteria_category', 'vendor_eval_items_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_evaluation_items');
    }
};
