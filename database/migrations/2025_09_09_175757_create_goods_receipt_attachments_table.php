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
        Schema::create('goods_receipt_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->string('file_name'); // ชื่อไฟล์ต้นฉบับ
            $table->string('file_path'); // path ที่เก็บไฟล์
            $table->string('file_type'); // mime type (pdf, jpg, png, etc.)
            $table->unsignedBigInteger('file_size'); // ขนาดไฟล์ (bytes)
            $table->string('description')->nullable(); // คำอธิบายไฟล์
            $table->unsignedBigInteger('uploaded_by'); // ผู้อัปโหลด
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_attachments');
    }
};
