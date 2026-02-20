<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementAnomaly extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'severity',
        'status',
        'title',
        'description',
        'details',
        'department_id',
        'vendor_id',
        'purchase_requisition_id',
        'purchase_order_id',
        'amount',
        'expected_amount',
        'deviation_percent',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'notified',
        'notified_at',
        'fingerprint',
    ];

    protected $casts = [
        'details' => 'array',
        'amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'deviation_percent' => 'decimal:2',
        'notified' => 'boolean',
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Anomaly types
    const TYPE_PRICE_ANOMALY = 'price_anomaly';
    const TYPE_SPLIT_PR = 'split_pr';
    const TYPE_BUDGET_OVERRUN = 'budget_overrun';
    const TYPE_VENDOR_CONCENTRATION = 'vendor_concentration';
    const TYPE_APPROVAL_DELAY = 'approval_delay';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    // Statuses
    const STATUS_OPEN = 'open';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PRICE_ANOMALY => 'ราคาผิดปกติ',
            self::TYPE_SPLIT_PR => 'แยก PR หลีกเลี่ยงวงเงิน',
            self::TYPE_BUDGET_OVERRUN => 'งบประมาณเกิน',
            self::TYPE_VENDOR_CONCENTRATION => 'Vendor กระจุกตัว',
            self::TYPE_APPROVAL_DELAY => 'อนุมัติล่าช้า',
            default => $this->type,
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PRICE_ANOMALY => 'heroicon-o-currency-dollar',
            self::TYPE_SPLIT_PR => 'heroicon-o-scissors',
            self::TYPE_BUDGET_OVERRUN => 'heroicon-o-exclamation-triangle',
            self::TYPE_VENDOR_CONCENTRATION => 'heroicon-o-building-storefront',
            self::TYPE_APPROVAL_DELAY => 'heroicon-o-clock',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_INFO => 'info',
            default => 'gray',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'วิกฤต',
            self::SEVERITY_WARNING => 'เตือน',
            self::SEVERITY_INFO => 'แจ้งเตือน',
            default => $this->severity,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'รอตรวจสอบ',
            self::STATUS_ACKNOWLEDGED => 'รับทราบแล้ว',
            self::STATUS_RESOLVED => 'แก้ไขแล้ว',
            self::STATUS_DISMISSED => 'ยกเลิก',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'danger',
            self::STATUS_ACKNOWLEDGED => 'warning',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_DISMISSED => 'gray',
            default => 'gray',
        };
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACKNOWLEDGED]);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function resolve(int $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function dismiss(int $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function acknowledge(): void
    {
        $this->update(['status' => self::STATUS_ACKNOWLEDGED]);
    }

    public function markNotified(): void
    {
        $this->update([
            'notified' => true,
            'notified_at' => now(),
        ]);
    }
}
