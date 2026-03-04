<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoAmendment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'value_analysis_id',
        'amendment_number',
        'amendment_type',
        'amendment_reason',
        'previous_total_amount',
        'new_total_amount',
        'changes_snapshot',
        'status',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'previous_total_amount' => 'decimal:2',
        'new_total_amount' => 'decimal:2',
        'changes_snapshot' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'amendment_number' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function valueAnalysis(): BelongsTo
    {
        return $this->belongsTo(ValueAnalysis::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รออนุมัติ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ถูกปฏิเสธ',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    public function getAmountDiffAttribute(): float
    {
        return $this->new_total_amount - $this->previous_total_amount;
    }

    public function getAmountDiffPercentAttribute(): ?float
    {
        if (!$this->previous_total_amount || $this->previous_total_amount == 0) {
            return null;
        }
        return (($this->new_total_amount - $this->previous_total_amount) / $this->previous_total_amount) * 100;
    }

    public function getAmendmentTypeLabelAttribute(): string
    {
        return ValueAnalysis::AMENDMENT_TYPES[$this->amendment_type] ?? $this->amendment_type ?? '';
    }
}
