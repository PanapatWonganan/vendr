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
            // Check if columns exist before adding them to avoid errors
            if (!Schema::hasColumn('purchase_requisitions', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            if (!Schema::hasColumn('purchase_requisitions', 'request_date')) {
                $table->date('request_date')->default(now()->toDateString());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropColumn(['notes', 'request_date']);
        });
    }
};
