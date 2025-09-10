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
            // Add notes column if it doesn't exist
            if (!Schema::hasColumn('purchase_requisitions', 'notes')) {
                $table->text('notes')->nullable()->after('budget_code');
            }
            
            // Modify request_date to have default value
            if (Schema::hasColumn('purchase_requisitions', 'request_date')) {
                $table->date('request_date')->default(now()->toDateString())->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisitions', 'notes')) {
                $table->dropColumn('notes');
            }
            
            // We can't really revert the default value easily, so we'll leave it
        });
    }
};
