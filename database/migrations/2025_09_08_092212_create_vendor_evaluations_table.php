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
        Schema::create('vendor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            
            // Evaluation period and timing
            $table->string('evaluation_period'); // Q1-2024, Q2-2024, Annual-2024
            $table->date('evaluation_date');
            $table->date('period_start');
            $table->date('period_end');
            
            // Scoring
            $table->decimal('overall_score', 5, 2)->nullable(); // Auto-calculated
            $table->integer('total_criteria')->default(0); // Total criteria evaluated
            $table->integer('applicable_criteria')->default(0); // Criteria that are not N/A
            
            // Status and workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // Comments and feedback
            $table->text('general_comments')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['vendor_id', 'company_id']);
            $table->index(['evaluation_period', 'company_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_evaluations');
    }
};
