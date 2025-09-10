<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_requisition_id',
        'line_number',
        'item_code',
        'description',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_amount',
        'required_date',
        'specification',
        'status',
        'preferred_supplier_id',
        'remarks',
        'account_code',
        'technical_attachments',
        'ordered_quantity',
        'received_quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'ordered_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'required_date' => 'date',
    ];

    /**
     * Get the purchase requisition that owns the item.
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }
} 