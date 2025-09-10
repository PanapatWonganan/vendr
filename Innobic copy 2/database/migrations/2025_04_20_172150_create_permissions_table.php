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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module'); // e.g., requisition, purchase_order, supplier, etc.
            $table->string('action'); // e.g., create, read, update, delete, approve, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Create a unique index on the combination of module and action
            $table->unique(['module', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
