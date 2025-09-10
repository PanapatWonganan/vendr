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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique(); // PR-YYYYMMDD-XXXX format
            $table->foreignId('department_id')->constrained();
            $table->foreignId('requester_id')->constrained('users');
            $table->date('request_date');
            $table->date('required_date');
            $table->string('purpose')->nullable();
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', [
                'draft', 
                'pending_approval', 
                'approved', 
                'rejected', 
                'in_process', 
                'completed', 
                'cancelled'
            ])->default('draft');
            $table->foreignId('current_approver_id')->nullable()->constrained('users');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->string('budget_code')->nullable();
            $table->string('project_code')->nullable();
            $table->boolean('is_budgeted')->default(true);
            $table->json('approval_history')->nullable();
            $table->json('rejection_reasons')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
