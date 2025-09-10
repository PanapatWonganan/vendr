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
            // PR Email Notification Preferences
            $table->boolean('email_pr_notifications')->default(true)->comment('Master toggle for all PR notifications');
            $table->boolean('email_pr_approved')->default(true)->comment('Email when PR is approved');
            $table->boolean('email_pr_rejected')->default(true)->comment('Email when PR is rejected');
            $table->boolean('email_pr_created')->default(true)->comment('Email when PR is created for user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_pr_notifications',
                'email_pr_approved', 
                'email_pr_rejected',
                'email_pr_created'
            ]);
        });
    }
};
