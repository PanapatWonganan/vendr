<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One clause section of a TOR template (master data).
 */
class TorTemplateSection extends Model
{
    // Section rendering types
    const TYPE_CLAUSE = 'clause';       // ย่อหน้า + ข้อย่อย (body + config.items)

    const TYPE_SCOPE = 'scope';         // ข้อ 5 ขอบเขตงาน (intro + items แบบ tree)

    const TYPE_TIMELINE = 'timeline';   // ข้อ 6 ระยะเวลา (เลือก 1 ใน 3 mode)

    const TYPE_PAYMENT = 'payment';     // ข้อ 7 การชำระเงิน (multi-option + งวด รวม 100%)

    const TYPE_DELIVERY = 'delivery';   // ข้อ 8 การส่งมอบงาน (doc checklist + กรรมการ)

    /**
     * Use default database connection (shared across all companies).
     * Not hardcoded so tests can use sqlite in-memory.
     */
    public function getConnectionName()
    {
        return app()->runningUnitTests() ? config('database.default') : 'mysql';
    }

    protected $fillable = [
        'tor_template_id', 'section_key', 'display_number', 'title_th',
        'section_type', 'body_default', 'config', 'is_optional', 'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_optional' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TorTemplate::class, 'tor_template_id');
    }
}
