<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_trackings', function (Blueprint $table) {
            $table->unsignedBigInteger('tor_id')->nullable()->after('purchase_order_id');
            $table->index('tor_id');
        });
    }

    public function down(): void
    {
        Schema::table('sla_trackings', function (Blueprint $table) {
            $table->dropIndex(['tor_id']);
            $table->dropColumn('tor_id');
        });
    }
};
