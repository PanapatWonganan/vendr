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
        Schema::table('goods_receipts', function (Blueprint $table) {
            // Make all potentially nullable fields actually nullable
            $table->unsignedBigInteger('received_by')->nullable()->change();
            $table->unsignedBigInteger('updated_by')->nullable()->change();
            $table->string('carrier')->nullable()->change();
            $table->string('tracking_number')->nullable()->change();
            $table->string('delivery_note_number')->nullable()->change();
            $table->text('rejection_reason')->nullable()->change();
            $table->text('milestone_description')->nullable()->change();
            $table->text('inspection_notes')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->json('documents')->nullable()->change();
            $table->text('quality_check_notes')->nullable()->change();
            $table->unsignedBigInteger('quality_checked_by')->nullable()->change();
            $table->timestamp('quality_checked_at')->nullable()->change();
            $table->timestamp('committee_notified_at')->nullable()->change();
            $table->unsignedBigInteger('reviewed_by')->nullable()->change();
            $table->timestamp('reviewed_at')->nullable()->change();
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            // Note: We're not reverting this as it might break existing data
        });
    }
};
