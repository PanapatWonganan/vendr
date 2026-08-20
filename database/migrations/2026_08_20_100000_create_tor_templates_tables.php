<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TOR document-builder: clause template library.
 *
 * tor_templates          — 1 row per procurement type (ซื้อทั่วไป / ซื้อ Inventory /
 *                          จ้างบริการทั่วไป / จ้างบริการ Bidding / จ้างผลิต Inventory / เช่า)
 * tor_template_sections  — ordered clause sections per template. Shared boilerplate
 *                          uses placeholders ({{party}}, {{company_short}},
 *                          {{company_full}}, {{penalty_rate}}, {{penalty_base}})
 *                          resolved when a TOR is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tor_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();          // buy_general, service, rent, ...
            $table->string('name_th', 191);
            $table->string('name_en', 191)->nullable();
            $table->unsignedBigInteger('company_id')->nullable(); // null = ใช้ได้ทุกบริษัท
            $table->string('party_term', 50);              // ผู้ขาย / ผู้รับจ้าง / ผู้ให้เช่า
            $table->decimal('penalty_rate', 5, 2)->default(0.20); // % ต่อวัน
            $table->string('penalty_base', 100)->default('มูลค่าสินค้า'); // มูลค่าสินค้า / มูลค่าค่าจ้าง
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        Schema::create('tor_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tor_template_id')->constrained('tor_templates')->cascadeOnDelete();
            $table->string('section_key', 50);             // definitions, scope_of_work, payment, ...
            $table->string('display_number', 10)->nullable(); // "1".."13" (null = ส่วนหัว/preamble)
            $table->string('title_th', 191)->nullable();
            $table->string('section_type', 30)->default('clause'); // clause|scope|timeline|payment|delivery
            $table->longText('body_default')->nullable();  // ข้อความย่อหน้า default (แก้ไขได้)
            $table->json('config')->nullable();            // items ย่อย / options / doc checklist
            $table->boolean('is_optional')->default(false); // ตัดออกได้ตามลักษณะงาน (เช่น งานไม่มีงวด)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tor_template_id', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tor_template_sections');
        Schema::dropIfExists('tor_templates');
    }
};
