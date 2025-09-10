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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // INV-YYYYMMDD-XXXX format
            $table->string('supplier_invoice_number');
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('purchase_order_id')->constrained();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('reference_number')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected', 
                'partial_payment', 
                'paid', 
                'cancelled'
            ])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->json('payment_details')->nullable(); // Store payment transaction details
            $table->json('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->json('document_scan')->nullable(); // Store path to scanned invoice
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
