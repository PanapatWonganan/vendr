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
        Schema::create('sla_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Reference to PR or PO
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            // Tracking Stage
            $table->enum('stage', [
                'pr_submission_to_approval',  // Stage 1: PR Submitted → PR Approved
                'pr_approval_to_po_creation', // Stage 2: PR Approved → PO Created
                'po_creation_to_approval',    // Stage 3: PO Created → PO Approved
                'full_cycle'                  // Full: PR Created → PO Approved
            ]);

            // Procurement Method & SLA Standard
            $table->string('procurement_method')->nullable();
            $table->integer('sla_standard_days'); // กรอบเวลามาตรฐาน (9, 25, 34 วัน)

            // Tracking Dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('actual_working_days')->nullable(); // วันทำการจริงที่ใช้

            // SLA Calculation
            $table->decimal('sla_percentage', 5, 2)->nullable(); // actual/standard * 100
            $table->enum('sla_grade', ['S', 'A', 'B', 'C', 'D', 'F'])->nullable();
            $table->integer('days_difference')->nullable(); // บวก = ช้า, ลบ = เร็ว
            $table->enum('status', ['on_time', 'late', 'in_progress'])->default('in_progress');

            // Additional Info
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('purchase_requisition_id')->references('id')->on('purchase_requisitions')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');

            // Indexes
            $table->index('stage');
            $table->index('sla_grade');
            $table->index('status');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_trackings');
    }
};
