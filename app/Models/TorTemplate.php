<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TOR clause template — 1 row per procurement type.
 * Master data shared across companies (company_id null = ทุกบริษัท).
 */
class TorTemplate extends Model
{
    // Procurement types (dropdown "ประเภทการจัดซื้อจัดจ้าง" in the workflow spec)
    const TYPE_BUY_GENERAL = 'buy_general';        // ซื้อทั่วไป

    const TYPE_BUY_INVENTORY = 'buy_inventory';    // ซื้อ Inventory

    const TYPE_SERVICE = 'service';                // จ้างบริการทั่วไป

    const TYPE_SERVICE_BIDDING = 'service_bidding'; // จ้างบริการ (Bidding)

    const TYPE_MANUFACTURE = 'manufacture';        // จ้างผลิต Inventory

    const TYPE_RENT = 'rent';                      // เช่า

    const TYPES = [
        self::TYPE_BUY_GENERAL => 'ซื้อทั่วไป',
        self::TYPE_BUY_INVENTORY => 'ซื้อ Inventory',
        self::TYPE_SERVICE => 'จ้างบริการทั่วไป',
        self::TYPE_SERVICE_BIDDING => 'จ้างบริการ (Bidding)',
        self::TYPE_MANUFACTURE => 'จ้างผลิต Inventory',
        self::TYPE_RENT => 'เช่า',
    ];

    /**
     * Use default database connection (shared across all companies).
     * Not hardcoded so tests can use sqlite in-memory.
     */
    public function getConnectionName()
    {
        return app()->runningUnitTests() ? config('database.default') : 'mysql';
    }

    protected $fillable = [
        'code', 'name_th', 'name_en', 'company_id', 'party_term',
        'penalty_rate', 'penalty_base', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'penalty_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(TorTemplateSection::class)->orderBy('sort_order');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Resolve placeholders and build the document_sections snapshot for a new TOR.
     *
     * @param  array{company_full?:string, company_short?:string}  $context
     */
    public function buildDocumentSections(array $context = []): array
    {
        $replacements = [
            '{{party}}' => $this->party_term,
            '{{company_full}}' => $context['company_full'] ?? 'บริษัท อินโนบิก นูทริชั่น จำกัด',
            '{{company_short}}' => $context['company_short'] ?? 'INNT',
            '{{penalty_rate}}' => rtrim(rtrim(number_format((float) $this->penalty_rate, 2), '0'), '.'),
            '{{penalty_base}}' => $this->penalty_base,
        ];

        $resolve = function ($value) use (&$resolve, $replacements) {
            if (is_string($value)) {
                return strtr($value, $replacements);
            }
            if (is_array($value)) {
                return array_map($resolve, $value);
            }

            return $value;
        };

        return $this->sections->map(fn (TorTemplateSection $s) => [
            'key' => $s->section_key,
            'number' => $s->display_number,
            'title' => $resolve($s->title_th),
            'type' => $s->section_type,
            'hidden' => false,
            'body' => $resolve($s->body_default),
            'data' => $resolve($s->config ?? []),
        ])->values()->all();
    }
}
