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
            // Add company_id
            if (!Schema::hasColumn('goods_receipts', 'company_id')) {
                $table->foreignId('company_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            // Add new columns for GR/MR system
            if (!Schema::hasColumn('goods_receipts', 'receipt_number')) {
                $table->string('receipt_number')->after('gr_number')->unique();
            }
            
            if (!Schema::hasColumn('goods_receipts', 'delivery_milestone')) {
                $table->integer('delivery_milestone')->after('receipt_date')->comment('งวดที่ส่งมอบ');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'milestone_description')) {
                $table->string('milestone_description')->nullable()->after('delivery_milestone');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'milestone_percentage')) {
                $table->decimal('milestone_percentage', 5, 2)->default(0)->after('milestone_description')->comment('เปอร์เซ็นต์ของงวด');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'inspection_status')) {
                $table->enum('inspection_status', ['pending', 'passed', 'failed', 'partial'])->default('pending')->after('milestone_percentage');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'inspection_notes')) {
                $table->text('inspection_notes')->nullable()->after('inspection_status');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'committee_notified_at')) {
                $table->datetime('committee_notified_at')->nullable();
            }
            
            if (!Schema::hasColumn('goods_receipts', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
            }
            
            if (!Schema::hasColumn('goods_receipts', 'reviewed_at')) {
                $table->datetime('reviewed_at')->nullable();
            }
            
            if (!Schema::hasColumn('goods_receipts', 'created_by')) {
                $table->foreignId('created_by')->constrained('users');
            }
            
            // Add indexes
            $table->index(['company_id', 'status']);
            $table->index(['purchase_order_id', 'delivery_milestone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['purchase_order_id', 'delivery_milestone']);
            
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->dropColumn('receipt_number');
            $table->dropColumn('delivery_milestone');
            $table->dropColumn('milestone_description');
            $table->dropColumn('milestone_percentage');
            $table->dropColumn('inspection_status');
            $table->dropColumn('inspection_notes');
            $table->dropColumn('rejection_reason');
            $table->dropColumn('committee_notified_at');
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn('reviewed_by');
            $table->dropColumn('reviewed_at');
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};