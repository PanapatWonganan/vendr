<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueAnalysis extends Model
{
    use HasFactory;

    protected $table = 'value_analysis';

    protected $fillable = [
        'va_number',
        'purchase_requisition_id',
        'work_type',
        'procurement_method',
        'procured_from',
        'agreed_amount',
        'total_budget',
        'currency',
        'analysis_objective',
        'analysis_scope',
        'evaluation_criteria',
        'alternatives',
        'comparison_matrix',
        'recommendations',
        'conclusion',
        'status',
        'created_by',
        'analyzed_by',
        'approved_by',
        'analysis_date',
        'approved_at',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'agreed_amount' => 'decimal:2',
        'evaluation_criteria' => 'array',
        'alternatives' => 'array',
        'comparison_matrix' => 'array',
        'analysis_date' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analyzer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper Methods
    public static function generateVANumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $prefix = "VA-{$year}{$month}{$day}";
        
        $lastVA = static::where('va_number', 'like', "{$prefix}%")
            ->orderBy('va_number', 'desc')
            ->first();

        if ($lastVA) {
            $lastNumber = (int) substr($lastVA->va_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'ร่าง',
            'in_progress' => 'กำลังวิเคราะห์',
            'completed' => 'วิเคราะห์เสร็จสิ้น',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            default => $this->status ?? 'ไม่ระบุสถานะ',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'in_progress' => 'bg-warning',
            'completed' => 'bg-info',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getWorkTypeLabelAttribute(): string
    {
        $options = PurchaseRequisition::getWorkTypeOptions();
        return $options[$this->work_type] ?? $this->work_type ?? 'ไม่ระบุ';
    }

    public function getProcurementMethodLabelAttribute(): string
    {
        $options = PurchaseRequisition::getProcurementMethodOptions();
        return $options[$this->procurement_method] ?? $this->procurement_method ?? 'ไม่ระบุ';
    }

    // Price Negotiation Calculations
    public function getPriceDifferenceAttribute(): ?float
    {
        if (!$this->total_budget || !$this->agreed_amount) {
            return null;
        }
        return $this->total_budget - $this->agreed_amount;
    }

    public function getPriceDifferencePercentageAttribute(): ?float
    {
        if (!$this->total_budget || !$this->agreed_amount || $this->total_budget == 0) {
            return null;
        }
        return (($this->total_budget - $this->agreed_amount) / $this->total_budget) * 100;
    }

    public function getNegotiationResultAttribute(): string
    {
        $percentage = $this->price_difference_percentage;
        if ($percentage === null) {
            return 'ไม่สามารถคำนวณได้';
        }
        
        if ($percentage > 0) {
            return 'ประหยัดได้ ' . number_format($percentage, 2) . '%';
        } elseif ($percentage < 0) {
            return 'เกินงบประมาณ ' . number_format(abs($percentage), 2) . '%';
        } else {
            return 'ตรงตามงบประมาณ';
        }
    }

    public function getNegotiationStatusClassAttribute(): string
    {
        $percentage = $this->price_difference_percentage;
        if ($percentage === null) {
            return 'text-muted';
        }
        
        if ($percentage > 0) {
            return 'text-success'; // ประหยัดได้
        } elseif ($percentage < 0) {
            return 'text-danger'; // เกินงบ
        } else {
            return 'text-info'; // ตรงงบ
        }
    }

    // Scopes
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // Business Logic Methods
    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'in_progress']);
    }

    public function canApprove(): bool
    {
        return $this->status === 'completed';
    }

    public function startAnalysis(): bool
    {
        if ($this->status === 'draft') {
            $this->update([
                'status' => 'in_progress',
                'analysis_date' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->status === 'in_progress') {
            $this->update(['status' => 'completed']);
            return true;
        }
        return false;
    }

    public function approve(int $approverId): bool
    {
        if ($this->status === 'completed') {
            $this->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function reject(): bool
    {
        if (in_array($this->status, ['completed', 'in_progress'])) {
            $this->update(['status' => 'rejected']);
            return true;
        }
        return false;
    }
}
