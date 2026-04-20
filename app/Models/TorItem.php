<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tor_id',
        'item_number',
        'description',
        'specifications',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_total_price',
        'delivery_location',
        'required_date',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total_price' => 'decimal:2',
        'required_date' => 'date',
    ];

    public function termsOfReference(): BelongsTo
    {
        return $this->belongsTo(TermsOfReference::class, 'tor_id');
    }
}
