<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tor_approval_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tor_id');
            $table->string('action', 50); // submitted, reviewed, approved, rejected, returned, cancelled, amended
            $table->unsignedBigInteger('performed_by');
            $table->timestamp('performed_at');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('tor_id')
                ->references('id')
                ->on('terms_of_references')
                ->cascadeOnDelete();

            $table->index(['tor_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tor_approval_history');
    }
};
