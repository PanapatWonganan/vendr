<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Anomaly classification
            $table->string('type'); // price_anomaly, split_pr, budget_overrun, vendor_concentration, approval_delay
            $table->string('severity'); // info, warning, critical
            $table->string('status')->default('open'); // open, acknowledged, resolved, dismissed

            // What triggered the anomaly
            $table->string('title');
            $table->text('description');
            $table->json('details')->nullable(); // structured data for each anomaly type

            // Related entities (polymorphic-like, nullable)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            // Financial context
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->decimal('deviation_percent', 8, 2)->nullable();

            // Resolution
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Notification tracking
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();

            // Prevent duplicates within scan window
            $table->string('fingerprint')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'type', 'status']);
            $table->index(['company_id', 'severity', 'status']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_anomalies');
    }
};
