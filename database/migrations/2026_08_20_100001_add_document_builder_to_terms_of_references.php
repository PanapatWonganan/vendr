<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TOR document-builder: link a TOR to its clause template and store the
 * user-edited document snapshot.
 *
 * document_sections holds the resolved + edited sections as JSON (see
 * docs/TOR_DOCUMENT_BUILDER_SCHEMA.md). Existing flat columns
 * (scope_of_work, payment_terms, ...) stay untouched for backward compat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms_of_references', function (Blueprint $table) {
            $table->unsignedBigInteger('tor_template_id')->nullable()->after('tor_type');
            $table->string('procurement_type', 50)->nullable()->after('tor_template_id'); // buy_general|buy_inventory|service|service_bidding|manufacture|rent
            $table->string('party_term', 50)->nullable()->after('procurement_type');      // snapshot จาก template ตอนสร้าง
            $table->json('document_sections')->nullable()->after('penalty_clause');

            $table->foreign('tor_template_id')->references('id')->on('tor_templates')->nullOnDelete();
            $table->index('procurement_type');
        });
    }

    public function down(): void
    {
        Schema::table('terms_of_references', function (Blueprint $table) {
            $table->dropForeign(['tor_template_id']);
            $table->dropIndex(['procurement_type']);
            $table->dropColumn(['tor_template_id', 'procurement_type', 'party_term', 'document_sections']);
        });
    }
};
