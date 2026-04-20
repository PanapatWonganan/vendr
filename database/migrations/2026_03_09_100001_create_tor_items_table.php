<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tor_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tor_id');
            $table->integer('item_number');
            $table->text('description');
            $table->text('specifications')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 50)->nullable();
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total_price', 15, 2)->nullable();
            $table->string('delivery_location', 500)->nullable();
            $table->date('required_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('tor_id')
                ->references('id')
                ->on('terms_of_references')
                ->cascadeOnDelete();

            $table->index('tor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tor_items');
    }
};
