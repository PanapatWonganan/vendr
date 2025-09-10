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
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('level');
            $table->enum('approval_type', ['purchase_requisition', 'purchase_order', 'payment'])->default('purchase_requisition');
            $table->decimal('threshold_amount', 15, 2)->nullable()->default(null);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Each level must be unique within an approval type
            $table->unique(['level', 'approval_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_levels');
    }
};
