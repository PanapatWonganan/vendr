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
        Schema::table('value_analysis', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_va_id')->nullable()->after('id');
            $table->unsignedInteger('revision_number')->default(1)->after('parent_va_id');
            $table->string('amendment_type')->nullable()->after('status');
            $table->text('amendment_reason')->nullable()->after('amendment_type');

            $table->foreign('parent_va_id')
                ->references('id')
                ->on('value_analysis')
                ->nullOnDelete();

            $table->index(['parent_va_id', 'revision_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('value_analysis', function (Blueprint $table) {
            $table->dropForeign(['parent_va_id']);
            $table->dropIndex(['parent_va_id', 'revision_number']);
            $table->dropColumn(['parent_va_id', 'revision_number', 'amendment_type', 'amendment_reason']);
        });
    }
};
