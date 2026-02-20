<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('email');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->string('telegram_otp')->nullable()->after('telegram_username');
            $table->timestamp('telegram_otp_expires_at')->nullable()->after('telegram_otp');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_otp',
                'telegram_otp_expires_at',
                'telegram_linked_at',
            ]);
        });
    }
};
