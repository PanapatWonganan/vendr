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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // SOW specific fields
            $table->date('start_date')->nullable()->after('po_title')->comment('วันที่เริ่มต้นโครงการ');
            $table->date('end_date')->nullable()->after('start_date')->comment('วันที่สิ้นสุดโครงการ');
            $table->string('delivery_period')->nullable()->after('end_date')->comment('ระยะเวลาส่งมอบ');
            $table->integer('total_phases')->nullable()->after('delivery_period')->comment('จำนวนงวดทั้งหมด');
            $table->text('delivery_phases')->nullable()->after('total_phases')->comment('รายละเอียดการส่งมอบแต่ละงวด');
            
            // Contact and delivery information
            $table->string('delivery_location')->nullable()->after('delivery_phases')->comment('สถานที่จัดส่ง');
            $table->string('contact_person')->nullable()->after('delivery_location')->comment('ผู้ติดต่อ');
            $table->string('contact_phone')->nullable()->after('contact_person')->comment('เบอร์โทรศัพท์ผู้ติดต่อ');
            
            // Warranty fields (for purchase type)
            $table->integer('warranty_days')->nullable()->after('contact_phone')->comment('จำนวนวันรับประกัน (สำหรับซื้อ)');
            $table->integer('extended_warranty_days')->nullable()->after('warranty_days')->comment('จำนวนวันขยายรับประกัน');
            
            // Area size (for rent type)
            $table->decimal('area_size', 10, 2)->nullable()->after('extended_warranty_days')->comment('ขนาดพื้นที่ (ตารางเมตร - สำหรับเช่า)');
            
            // Document metadata
            $table->string('document_code')->nullable()->after('area_size')->comment('รหัสเอกสาร (PCM-002 หรือ PCMN-002-FO)');
            $table->json('sow_metadata')->nullable()->after('document_code')->comment('ข้อมูลเพิ่มเติมสำหรับ SOW');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date', 
                'delivery_period',
                'total_phases',
                'delivery_phases',
                'delivery_location',
                'contact_person',
                'contact_phone',
                'warranty_days',
                'extended_warranty_days',
                'area_size',
                'document_code',
                'sow_metadata'
            ]);
        });
    }
};
