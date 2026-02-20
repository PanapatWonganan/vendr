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
        Schema::table('departments', function (Blueprint $table) {
            $table->decimal('monthly_budget', 15, 2)->nullable()->after('is_active');
            $table->integer('budget_alert_threshold')->default(80)->after('monthly_budget'); // percent
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['monthly_budget', 'budget_alert_threshold']);
        });
    }
};
