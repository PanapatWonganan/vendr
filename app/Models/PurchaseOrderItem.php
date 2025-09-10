<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_requisition_item_id',
        'line_number',
        'item_code',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'line_total',
        'expected_delivery_date',
        'status',
        'received_quantity',
        'returned_quantity',
        'remarks',
        'tax_rate',
        'tax_amount',
        'discount_percent',
        'discount_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'expected_delivery_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseRequisitionItem::class);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->line_total - $this->discount_amount + $this->tax_amount;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'ordered' => 'bg-info',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            // Auto-calculate line total
            if ($item->quantity && $item->unit_price) {
                $item->line_total = $item->quantity * $item->unit_price;
            }
            
            // Auto-calculate tax amount
            if ($item->line_total && $item->tax_rate) {
                $item->tax_amount = $item->line_total * ($item->tax_rate / 100);
            }
            
            // Auto-calculate discount amount
            if ($item->line_total && $item->discount_percent) {
                $item->discount_amount = $item->line_total * ($item->discount_percent / 100);
            }
        });
    }
}