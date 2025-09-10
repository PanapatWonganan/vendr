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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_po_approved')->default(true)->after('email');
            $table->boolean('email_po_rejected')->default(true)->after('email_po_approved');
            $table->boolean('email_po_notifications')->default(true)->after('email_po_rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_po_approved', 'email_po_rejected', 'email_po_notifications']);
        });
    }
};
