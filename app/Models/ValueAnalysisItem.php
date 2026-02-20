<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueAnalysisItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'value_analysis_id',
        'purchase_requisition_item_id',
        'line_number',
        'item_code',
        'description',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_amount',
        'agreed_unit_price',
        'agreed_amount',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'agreed_unit_price' => 'decimal:2',
        'agreed_amount' => 'decimal:2',
    ];

    // Auto-calculate agreed_amount on save
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->quantity && $item->agreed_unit_price) {
                $item->agreed_amount = $item->quantity * $item->agreed_unit_price;
            }
        });
    }

    // Relationships
    public function valueAnalysis(): BelongsTo
    {
        return $this->belongsTo(ValueAnalysis::class);
    }

    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }

    // Computed attributes
    public function getPriceDifferenceAttribute(): float
    {
        return ($this->estimated_unit_price ?? 0) - ($this->agreed_unit_price ?? 0);
    }

    public function getSavingsPercentAttribute(): ?float
    {
        if (!$this->estimated_unit_price || $this->estimated_unit_price == 0) {
            return null;
        }
        return (($this->estimated_unit_price - $this->agreed_unit_price) / $this->estimated_unit_price) * 100;
    }
}
