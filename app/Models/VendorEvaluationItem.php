<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEvaluationItem extends Model
{
    protected $fillable = [
        'vendor_evaluation_id',
        'criteria_category',
        'criteria_name',
        'criteria_description',
        'score',
        'is_applicable',
        'comments',
        'evidence',
        'weight',
    ];

    protected $casts = [
        'is_applicable' => 'boolean',
        'weight' => 'decimal:2',
    ];

    // Relationships
    public function vendorEvaluation(): BelongsTo
    {
        return $this->belongsTo(VendorEvaluation::class);
    }

    // Scopes
    public function scopeApplicable($query)
    {
        return $query->where('is_applicable', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('criteria_category', $category);
    }

    // Methods
    public function getScoreTextAttribute()
    {
        if (!$this->is_applicable) return 'N/A';
        
        return match($this->score) {
            4 => 'ดีมาก',
            3 => 'ดี', 
            2 => 'พอใช้',
            1 => 'ควรปรับปรุง',
            default => 'ยังไม่ได้ประเมิน'
        };
    }

    public function getScoreColorAttribute()
    {
        if (!$this->is_applicable) return 'gray';
        
        return match($this->score) {
            4 => 'success',
            3 => 'info',
            2 => 'warning', 
            1 => 'danger',
            default => 'gray'
        };
    }

    // Static methods for default criteria
    public static function getDefaultCriteria(): array
    {
        return [
            'quality' => [
                'name' => 'ด้านคุณภาพ (Quality)',
                'items' => [
                    'มีคุณภาพตามข้อกำหนด',
                    'สินค้าสมบูรณ์ไม่มีข้อบกพร่อง',
                    'มีใบรับรองคุณภาพ',
                    'ได้รับการรับรองมาตรฐาน',
                ]
            ],
            'delivery' => [
                'name' => 'ด้านการส่งมอบ (Delivery)',
                'items' => [
                    'ส่งมอบตรงเวลา',
                    'บรรจุภัณฑ์เหมาะสม',
                    'มีการแจ้งล่วงหน้าหากมีปัญหา',
                    'มีระบบติดตามสินค้า',
                ]
            ],
            'service' => [
                'name' => 'การให้บริการ (Service)',
                'items' => [
                    'ตอบสนองอย่างรวดเร็ว',
                    'มีการบริการหลังการขาย',
                    'พนักงานมีความรู้และประสบการณ์',
                    'แก้ไขปัญหาได้อย่างมีประสิทธิภาพ',
                ]
            ],
            'performance' => [
                'name' => 'ด้านการดำเนินการ (Performance)',
                'items' => [
                    'ปฏิบัติตามสัญญาและข้อตกลง',
                    'มีเอกสารครบถ้วนและถูกต้อง',
                    'ปฏิบัติตามกฎระเบียบและข้อกำหนด',
                    'มีประสิทธิภาพในการทำงาน',
                ]
            ],
        ];
    }
    
    public static function getCategoryOptions(): array
    {
        return [
            'quality' => 'ด้านคุณภาพ (Quality)',
            'delivery' => 'ด้านการส่งมอบ (Delivery)', 
            'service' => 'การให้บริการ (Service)',
            'performance' => 'ด้านการดำเนินการ (Performance)',
        ];
    }
}
