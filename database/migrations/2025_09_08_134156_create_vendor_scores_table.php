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
        Schema::create('vendor_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Period tracking
            $table->year('year');
            $table->tinyInteger('quarter')->nullable(); // 1-4 for quarterly, null for yearly
            $table->tinyInteger('month')->nullable(); // 1-12 for monthly tracking
            
            // Score data
            $table->decimal('total_score', 10, 2)->default(0);
            $table->decimal('total_weighted_score', 10, 2)->default(0); // Score weighted by PO value
            $table->decimal('total_po_value', 15, 2)->default(0); // Total PO value for weighting
            $table->integer('evaluation_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable(); // 0-4 scale
            $table->decimal('weighted_average_score', 5, 2)->nullable(); // Weighted by PO value
            $table->char('grade', 2)->nullable(); // A, B, C, D
            $table->char('weighted_grade', 2)->nullable(); // Grade from weighted score
            
            // Score breakdown by category
            $table->json('category_scores')->nullable(); // {quality: 3.5, delivery: 3.2, etc}
            $table->json('category_counts')->nullable(); // Count per category
            
            // Trend tracking
            $table->decimal('previous_score', 5, 2)->nullable();
            $table->enum('trend', ['up', 'down', 'stable'])->nullable();
            $table->decimal('score_change', 5, 2)->nullable(); // Difference from previous
            
            // Meta data
            $table->date('last_evaluation_date')->nullable();
            $table->foreignId('last_evaluation_id')->nullable()->constrained('vendor_evaluations');
            $table->json('evaluation_ids')->nullable(); // Array of evaluation IDs included
            
            $table->timestamps();
            
            // Indexes for fast queries
            $table->unique(['vendor_id', 'company_id', 'year', 'quarter', 'month']);
            $table->index(['vendor_id', 'company_id', 'year']);
            $table->index(['company_id', 'grade']);
            $table->index(['company_id', 'weighted_grade']);
            $table->index('average_score');
            $table->index('weighted_average_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_scores');
    }
};
