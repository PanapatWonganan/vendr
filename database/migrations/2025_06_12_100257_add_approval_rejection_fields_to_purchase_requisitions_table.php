<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            // Check and add approval fields
            if (!Schema::hasColumn('purchase_requisitions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_requisitions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_requisitions', 'approval_comments')) {
                $table->text('approval_comments')->nullable()->after('status');
            }
            
            // Check and add rejection fields
            if (!Schema::hasColumn('purchase_requisitions', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_requisitions', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_requisitions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
        });
        
        // Add foreign key constraints if they don't exist
        if (!$this->foreignKeyExists('purchase_requisitions', 'purchase_requisitions_approved_by_foreign')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }
        
        if (!$this->foreignKeyExists('purchase_requisitions', 'purchase_requisitions_rejected_by_foreign')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            // Drop foreign keys if they exist
            if ($this->foreignKeyExists('purchase_requisitions', 'purchase_requisitions_approved_by_foreign')) {
                $table->dropForeign(['approved_by']);
            }
            if ($this->foreignKeyExists('purchase_requisitions', 'purchase_requisitions_rejected_by_foreign')) {
                $table->dropForeign(['rejected_by']);
            }
            
            // Drop columns if they exist
            $columnsToRemove = ['approved_by', 'approved_at', 'approval_comments', 'rejected_by', 'rejected_at', 'rejection_reason'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('purchase_requisitions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
    
    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists($table, $keyName)
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = ?
        ", [$table, $keyName]);
        
        return count($constraints) > 0;
    }
};
