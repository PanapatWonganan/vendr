<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_of_references', function (Blueprint $table) {
            $table->id();
            $table->string('tor_number', 50)->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('title', 500);
            $table->string('project_name', 500)->nullable();
            $table->string('project_code', 100)->nullable();
            $table->string('tor_type', 50)->default('goods'); // goods, services, construction, consulting
            $table->string('form_category', 50)->nullable(); // act_based, law_based
            $table->string('work_type', 50)->nullable(); // buy, hire, rent
            $table->string('procurement_method', 50)->nullable(); // agreement_price, invitation_bid, open_bid, special_1, special_2, selection
            $table->string('category', 100)->nullable(); // premium_products, advertising_services, etc.
            $table->text('background')->nullable();
            $table->text('objectives')->nullable();
            $table->text('scope_of_work');
            $table->text('deliverables')->nullable();
            $table->text('specifications')->nullable();
            $table->text('qualification_requirements')->nullable();
            $table->json('evaluation_criteria')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('warranty_requirements')->nullable();
            $table->text('penalty_clause')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->decimal('budget_estimate', 15, 2)->nullable();
            $table->string('currency', 3)->default('THB');
            $table->string('budget_code', 100)->nullable();
            $table->string('status', 50)->default('draft'); // draft, submitted, reviewing, approved, rejected, amended, cancelled, expired
            $table->string('priority', 50)->default('medium'); // low, medium, high, urgent
            $table->unsignedBigInteger('procurement_committee_id')->nullable();
            $table->unsignedBigInteger('inspection_committee_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->integer('revision_number')->default(0);
            $table->unsignedBigInteger('parent_tor_id')->nullable();
            $table->text('amendment_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tor_number');
            $table->index('status');
            $table->index('department_id');
            $table->index('company_id');
            $table->index('parent_tor_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_of_references');
    }
};
