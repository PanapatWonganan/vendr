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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('cancellation_reason');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            
            // Update status enum to include 'rejected'
            $table->enum('status', [
                'draft', 
                'pending_approval', 
                'approved', 
                'rejected',
                'sent_to_supplier', 
                'acknowledged', 
                'partially_received', 
                'fully_received', 
                'closed', 
                'cancelled'
            ])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'rejected_by', 'rejected_at']);
            
            // Restore original status enum
            $table->enum('status', [
                'draft', 
                'pending_approval', 
                'approved', 
                'sent_to_supplier', 
                'acknowledged', 
                'partially_received', 
                'fully_received', 
                'closed', 
                'cancelled'
            ])->default('draft')->change();
        });
    }
};
