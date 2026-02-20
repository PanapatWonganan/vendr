<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_assessments', function (Blueprint $table) {
            $table->json('dimension_scores')->nullable()->after('ai_recommendations')
                ->comment('คะแนนรายด้าน 6 มิติ: legal_status, company_age, capital, objective_match, internal_performance, po_history');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_assessments', function (Blueprint $table) {
            $table->dropColumn('dimension_scores');
        });
    }
};
