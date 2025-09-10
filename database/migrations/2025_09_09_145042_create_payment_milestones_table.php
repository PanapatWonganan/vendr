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
        Schema::dropIfExists('payment_milestones');
        Schema::create('payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->onDelete('set null');
            $table->string('milestone_name'); // ชื่องวด เช่น มัดจำ, งวดที่ 1, งวดสุดท้าย
            $table->text('description')->nullable(); // รายละเอียดงวด
            $table->decimal('amount', 15, 2); // จำนวนเงินที่ต้องจ่าย
            $table->decimal('percentage', 5, 2)->nullable(); // เปอร์เซ็นต์ของ PO ทั้งหมด
            $table->date('due_date'); // วันที่ครบกำหนดจ่าย
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable(); // วันที่จ่ายจริง
            $table->decimal('paid_amount', 15, 2)->nullable(); // จำนวนเงินที่จ่ายจริง
            $table->text('payment_notes')->nullable(); // หมายเหตุการจ่าย
            $table->string('payment_reference')->nullable(); // อ้างอิงเอกสารการจ่าย เช่น เลขที่เช็ค
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->integer('sequence')->default(1); // ลำดับงวด
            $table->boolean('is_deposit')->default(false); // เป็นมัดจำหรือไม่
            $table->boolean('requires_gr')->default(true); // ต้องมี GR ก่อนจ่ายหรือไม่
            $table->timestamps();
            
            $table->index(['purchase_order_id', 'sequence']);
            $table->index(['due_date', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_milestones');
    }
};
