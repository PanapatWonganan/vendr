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
        Schema::create('goods_receipt_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['delivery_note', 'invoice', 'inspection_report', 'other']);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('file_type');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->datetime('uploaded_at');
            $table->timestamps();
            
            $table->index('goods_receipt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_documents');
    }
};
