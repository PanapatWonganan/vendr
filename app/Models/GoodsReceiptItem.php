<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'inspection_status',
        'inspection_notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    const INSPECTION_PASSED = 'passed';
    const INSPECTION_FAILED = 'failed';
    const INSPECTION_PARTIAL = 'partial';

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function getInspectionStatusLabelAttribute()
    {
        return match ($this->inspection_status) {
            self::INSPECTION_PASSED => 'ผ่านการตรวจสอบ',
            self::INSPECTION_FAILED => 'ไม่ผ่านการตรวจสอบ',
            self::INSPECTION_PARTIAL => 'ผ่านบางส่วน',
            default => $this->inspection_status,
        };
    }

    public function getInspectionStatusBadgeClassAttribute()
    {
        return match ($this->inspection_status) {
            self::INSPECTION_PASSED => 'bg-success',
            self::INSPECTION_FAILED => 'bg-danger',
            self::INSPECTION_PARTIAL => 'bg-info',
            default => 'bg-secondary',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }
}