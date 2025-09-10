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
            $table->string('name')->after('id');
            $table->string('code')->unique()->after('name');
            $table->text('description')->nullable()->after('code');
            $table->unsignedBigInteger('manager_id')->nullable()->after('description');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('parent_id')->nullable()->after('manager_id');
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('set null');
            $table->boolean('is_active')->default(true)->after('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['name', 'code', 'description', 'manager_id', 'parent_id', 'is_active']);
        });
    }
};
