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
        Schema::create('value_analysis_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('value_analysis_id')->constrained('value_analysis')->onDelete('cascade');
            $table->foreignId('purchase_requisition_item_id')->nullable()->constrained('purchase_requisition_items')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('item_code', 50)->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2);
            $table->string('unit_of_measure', 50);

            // Price from PR (original estimated price)
            $table->decimal('estimated_unit_price', 15, 2)->default(0); // จาก PR
            $table->decimal('estimated_amount', 15, 2)->default(0);    // จาก PR (qty * estimated)

            // Agreed price after VA negotiation
            $table->decimal('agreed_unit_price', 15, 2)->default(0);   // ราคาที่ตกลงได้
            $table->decimal('agreed_amount', 15, 2)->default(0);       // qty * agreed_unit_price

            $table->text('remarks')->nullable(); // หมายเหตุต่อรายการ

            $table->timestamps();

            $table->unique(['value_analysis_id', 'line_number'], 'va_item_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('value_analysis_items');
    }
};
