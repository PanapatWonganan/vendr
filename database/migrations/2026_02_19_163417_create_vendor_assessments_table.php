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
        Schema::create('vendor_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();

            // DBD Data (ข้อมูลจาก กรมพัฒนาธุรกิจการค้า)
            $table->string('tax_id', 13)->index();
            $table->string('dbd_status')->nullable()->comment('สถานะนิติบุคคล: ดำเนินกิจการอยู่, เลิก, ล้มละลาย');
            $table->string('dbd_entity_type')->nullable()->comment('ประเภทนิติบุคคล');
            $table->decimal('dbd_registered_capital', 18, 2)->nullable()->comment('ทุนจดทะเบียน (บาท)');
            $table->string('dbd_name_th')->nullable();
            $table->string('dbd_name_en')->nullable();
            $table->text('dbd_address')->nullable();
            $table->date('dbd_registration_date')->nullable()->comment('วันที่จดทะเบียน');
            $table->json('dbd_objectives')->nullable()->comment('วัตถุประสงค์');
            $table->json('dbd_raw_data')->nullable()->comment('Raw JSON response from DBD API');
            $table->timestamp('dbd_fetched_at')->nullable();

            // AI Analysis Results
            $table->integer('risk_score')->nullable()->comment('AI risk score 0-100 (0=safest, 100=riskiest)');
            $table->string('risk_level')->nullable()->comment('low, medium, high, critical');
            $table->text('ai_summary')->nullable()->comment('AI summary in Thai');
            $table->json('ai_risk_factors')->nullable()->comment('Array of risk factors identified by AI');
            $table->json('ai_strengths')->nullable()->comment('Array of strengths identified by AI');
            $table->json('ai_recommendations')->nullable()->comment('Array of recommendations from AI');
            $table->text('ai_raw_response')->nullable()->comment('Full AI response text');
            $table->string('ai_model_used')->nullable()->comment('Which AI model was used');
            $table->timestamp('ai_analyzed_at')->nullable();

            // Combined Risk Assessment
            $table->integer('overall_risk_score')->nullable()->comment('Final combined risk score');
            $table->string('overall_risk_level')->nullable()->comment('Final risk level');
            $table->string('assessment_status')->default('pending')->comment('pending, dbd_fetched, analyzed, completed, failed');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'company_id']);
            $table->index('assessment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_assessments');
    }
};
