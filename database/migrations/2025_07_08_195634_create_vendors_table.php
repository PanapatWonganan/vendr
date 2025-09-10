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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            
            // ข้อมูลทั่วไป
            $table->string('company_name');
            $table->string('tax_id')->unique();
            $table->text('address');
            $table->string('work_category');
            $table->text('experience')->nullable();
            
            // ข้อมูล point person (ผู้ติดต่อประสานงาน)
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email');
            
            // สถานะของ vendor
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            
            // เอกสารแนบ (path สำหรับเก็บไฟล์)
            $table->json('documents')->nullable(); // สำหรับเก็บ path ของไฟล์เอกสาร
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Indexes
            $table->index('company_id');
            $table->index('status');
            $table->index('work_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
