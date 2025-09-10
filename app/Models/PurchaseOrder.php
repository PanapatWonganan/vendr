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
        'company_name',
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
        // SOW fields
        'start_date',
        'end_date',
        'delivery_period',
        'total_phases',
        'delivery_phases',
        'delivery_location',
        'contact_person',
        'contact_phone',
        'warranty_days',
        'extended_warranty_days',
        'area_size',
        'document_code',
        'sow_metadata',
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
        // SOW field casts
        'start_date' => 'date',
        'end_date' => 'date',
        'area_size' => 'decimal:2',
        'sow_metadata' => 'json',
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

    // Helper Methods
    public static function generatePoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $prefix = "PO-{$year}{$month}{$day}";
        $companyId = session('company_id', 1);
        
        // Try to generate unique number with retries
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            $lastPo = static::where('po_number', 'like', "{$prefix}%")
                ->where('company_id', $companyId)
                ->orderBy('po_number', 'desc')
                ->first();

            if ($lastPo) {
                $lastNumber = (int) substr($lastPo->po_number, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $poNumber = $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Check if this PO number already exists for this company
            $exists = static::where('po_number', $poNumber)
                ->where('company_id', $companyId)
                ->exists();
                
            if (!$exists) {
                return $poNumber;
            }
            
            // If exists, wait a bit and try again
            usleep(100000); // 0.1 second
        }
        
        // If all retries failed, use timestamp suffix
        return $prefix . '-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
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

    /**
     * Get the attachments for the purchase order.
     */
    public function attachments()
    {
        return $this->morphMany(ProcurementAttachment::class, 'attachable');
    }

    /**
     * Get the items for the purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the payment milestones for the purchase order.
     */
    public function paymentMilestones(): HasMany
    {
        return $this->hasMany(PaymentMilestone::class)->orderBy('milestone_number');
    }

    /**
     * Get the goods receipts for the purchase order.
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}
