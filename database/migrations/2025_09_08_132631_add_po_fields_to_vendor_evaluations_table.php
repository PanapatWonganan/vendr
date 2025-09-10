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
        Schema::table('vendor_evaluations', function (Blueprint $table) {
            // PO relationship
            $table->foreignId('purchase_order_id')->nullable()->after('vendor_id')
                ->constrained('purchase_orders')->onDelete('cascade');
            
            // Payment term information
            $table->integer('payment_term_number')->nullable()->after('purchase_order_id');
            $table->string('payment_term_description')->nullable()->after('payment_term_number');
            
            // Project information (cached from PO)
            $table->string('project_name')->nullable()->after('payment_term_description');
            
            // Committee members (JSON array)
            $table->json('committee_members')->nullable()->after('project_name');
            
            // Add index for faster queries
            $table->index(['purchase_order_id', 'payment_term_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_evaluations', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropIndex(['purchase_order_id', 'payment_term_number']);
            $table->dropColumn([
                'purchase_order_id',
                'payment_term_number', 
                'payment_term_description',
                'project_name',
                'committee_members'
            ]);
        });
    }
};
