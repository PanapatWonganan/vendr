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
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->foreignId('procurement_committee_id')->nullable()->after('payment_schedule')->constrained('users')->onDelete('set null')->comment('คณะกรรมการจัดหาพัสดุ');
            $table->foreignId('inspection_committee_id')->nullable()->after('procurement_committee_id')->constrained('users')->onDelete('set null')->comment('คณะกรรมการตรวจรับ');
            $table->foreignId('pr_approver_id')->nullable()->after('inspection_committee_id')->constrained('users')->onDelete('set null')->comment('ผู้อนุมัติ PR');
            $table->foreignId('other_stakeholder_id')->nullable()->after('pr_approver_id')->constrained('users')->onDelete('set null')->comment('ผู้เกี่ยวข้องอื่น');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropForeign(['procurement_committee_id']);
            $table->dropForeign(['inspection_committee_id']);
            $table->dropForeign(['pr_approver_id']);
            $table->dropForeign(['other_stakeholder_id']);
            $table->dropColumn(['procurement_committee_id', 'inspection_committee_id', 'pr_approver_id', 'other_stakeholder_id']);
        });
    }
};
