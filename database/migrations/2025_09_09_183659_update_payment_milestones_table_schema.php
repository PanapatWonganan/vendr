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
        // First, drop foreign keys
        Schema::table('payment_milestones', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_id']);
        });
        
        Schema::table('payment_milestones', function (Blueprint $table) {
            // Remove old columns that are not needed anymore
            $table->dropColumn(['goods_receipt_id', 'sequence', 'is_deposit', 'requires_gr', 'description']);
            
            // Rename milestone_name to milestone_title
            $table->renameColumn('milestone_name', 'milestone_title');
            
            // Add new columns
            $table->integer('milestone_number')->after('purchase_order_id'); // งวดที่ 1, 2, 3
            $table->text('payment_terms')->nullable()->after('amount'); // เงื่อนไขการจ่าย
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Update enum values for status
            $table->dropColumn('status');
        });
        
        // Add status column with new enum values
        Schema::table('payment_milestones', function (Blueprint $table) {
            $table->enum('status', ['pending', 'due', 'paid', 'overdue', 'cancelled'])->default('pending')->after('payment_terms');
        });
        
        // Add foreign key for updated_by
        Schema::table('payment_milestones', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_milestones', function (Blueprint $table) {
            // Reverse the changes
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['milestone_number', 'payment_terms', 'updated_by']);
            $table->dropColumn('status');
            
            // Restore old columns
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->integer('sequence')->default(1);
            $table->boolean('is_deposit')->default(false);
            $table->boolean('requires_gr')->default(true);
            $table->text('description')->nullable();
            
            $table->renameColumn('milestone_title', 'milestone_name');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
        });
    }
};
