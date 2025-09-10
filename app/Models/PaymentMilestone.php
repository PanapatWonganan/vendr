<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PaymentMilestone extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'milestone_number',
        'milestone_title',
        'percentage',
        'amount',
        'payment_terms',
        'due_date',
        'status',
        'paid_date',
        'paid_amount',
        'payment_reference',
        'payment_notes',
        'paid_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'milestone_number' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_DUE = 'due';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDueSoon($query, $days = 15)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_DUE => 'ถึงกำหนด',
            self::STATUS_PAID => 'จ่ายแล้ว',
            self::STATUS_OVERDUE => 'เลยกำหนด',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_DUE => 'bg-info',
            self::STATUS_PAID => 'bg-success',
            self::STATUS_OVERDUE => 'bg-danger',
            self::STATUS_CANCELLED => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function getDaysUntilDueAttribute()
    {
        if (!$this->due_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    public function getDueStatusAttribute()
    {
        $days = $this->days_until_due;

        if ($days === null) {
            return 'ไม่มีกำหนด';
        } elseif ($days < 0) {
            return 'เลยกำหนด ' . abs($days) . ' วัน';
        } elseif ($days == 0) {
            return 'ครบกำหนดวันนี้';
        } elseif ($days <= 7) {
            return 'ครบกำหนดใน ' . $days . ' วัน';
        } else {
            return 'ครบกำหนดใน ' . $days . ' วัน';
        }
    }

    public function getDueStatusBadgeClassAttribute()
    {
        $days = $this->days_until_due;

        if ($days === null) {
            return 'bg-secondary';
        } elseif ($days < 0) {
            return 'bg-danger';
        } elseif ($days <= 7) {
            return 'bg-warning';
        } else {
            return 'bg-info';
        }
    }

    public function canEdit()
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    public function canDelete()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canPay()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_DUE]);
    }

    public function needsReminder()
    {
        return $this->status === self::STATUS_PENDING &&
               $this->days_until_due !== null &&
               $this->days_until_due <= 15 &&
               $this->days_until_due > 0;
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_DUE => 'ถึงกำหนด',
            self::STATUS_PAID => 'จ่ายแล้ว',
            self::STATUS_OVERDUE => 'เลยกำหนด',
            self::STATUS_CANCELLED => 'ยกเลิก',
        ];
    }
}