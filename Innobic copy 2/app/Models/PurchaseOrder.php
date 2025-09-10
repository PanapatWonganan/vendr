<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'pr_id',
        'purchase_requisition_id',
        'po_number',
        'sap_po_number',
        'po_title',
        'work_type',
        'procurement_method',
        'vendor_id',
        'inspection_committee_id',
        'contact_name',
        'contact_email',
        'vendor_name',
        'total_amount',
        'currency',
        'stamp_duty',
        'delivery_schedule',
        'payment_schedule',
        'payment_terms',
        'operation_duration',
        'order_date',
        'expected_delivery_date',
        'priority',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        // Original fields (keeping for compatibility)
        'supplier_id', 
        'delivery_address',
        'department_id',
        'shipping_method',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'other_charges',
        'exchange_rate',
        'supplier_reference',
        'cancellation_reason',
        'rejection_reason',
        'rejected_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'closed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'approval_history' => 'array',
        'is_confirmed' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'supplier_id');
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function purchaseRequisitionByPrId(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'pr_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class);
    }

    public function inspectionCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspection_committee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PurchaseOrderFile::class);
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // Helper Methods
    public static function generatePoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $prefix = "PO-{$year}{$month}{$day}";
        
        $lastPo = static::where('po_number', 'like', "{$prefix}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
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
            'pending_approval' => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'sent_to_supplier' => 'ส่งให้ผู้ขายแล้ว',
            'acknowledged' => 'ผู้ขายรับทราบแล้ว',
            'partially_received' => 'รับบางส่วน',
            'fully_received' => 'รับครบแล้ว',
            'closed' => 'ปิดงาน',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'pending_approval' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'sent_to_supplier' => 'bg-info',
            'acknowledged' => 'bg-primary',
            'partially_received' => 'bg-warning',
            'fully_received' => 'bg-success',
            'closed' => 'bg-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getPriorityTextAttribute(): string
    {
        return match($this->priority ?? 'medium') {
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'urgent' => 'เร่งด่วน',
            default => 'ปานกลาง',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority ?? 'medium') {
            'low' => 'bg-secondary',
            'medium' => 'bg-info',
            'high' => 'bg-warning',
            'urgent' => 'bg-danger',
            default => 'bg-info',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canApprove(): bool
    {
        return in_array($this->status, ['pending_approval']);
    }

    public function canSendToVendor(): bool
    {
        return $this->status === 'approved';
    }

    public function canMarkReceived(): bool
    {
        return in_array($this->status, ['sent_to_supplier', 'acknowledged', 'partially_received']);
    }

    // Workflow Methods
    public function submitForApproval(): bool
    {
        if ($this->status === 'draft') {
            $this->update(['status' => 'pending_approval']);
            return true;
        }
        return false;
    }

    public function approve(int $approverId): bool
    {
        if ($this->status === 'pending_approval') {
            $this->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function sendToVendor(): bool
    {
        if ($this->status === 'approved') {
            $this->update([
                'status' => 'sent_to_supplier',
                'sent_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function markReceived(bool $isFullyReceived = true): bool
    {
        if (in_array($this->status, ['sent_to_supplier', 'acknowledged', 'partially_received'])) {
            $newStatus = $isFullyReceived ? 'fully_received' : 'partially_received';
            $this->update(['status' => $newStatus]);
            return true;
        }
        return false;
    }

    public function close(): bool
    {
        if ($this->status === 'fully_received') {
            $this->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function reject(int $rejectedBy, string $reason): bool
    {
        if ($this->status === 'pending_approval') {
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    public function cancel(string $reason): bool
    {
        if (!in_array($this->status, ['closed', 'cancelled'])) {
            $this->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);
            return true;
        }
        return false;
    }
}
