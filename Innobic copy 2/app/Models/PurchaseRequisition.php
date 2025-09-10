<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'pr_number',
        'title',
        'description',
        'date',
        'category',
        'work_type',
        'procurement_method',
        'procurement_budget',
        'delivery_schedule',
        'payment_schedule',
        'procurement_committee_id',
        'inspection_committee_id',
        'pr_approver_id',
        'other_stakeholder_id',
        'department',
        'department_id',
        'requester_id',
        'created_by',
        'request_date',
        'required_date',
        'purpose',
        'justification',
        'priority',
        'supplier_name',
        'supplier_contact',
        'supplier_address',
        'supplier_phone',
        'supplier_email',
        'expected_delivery_date',
        'total_amount',
        'currency',
        'budget_code',
        'notes',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'approved_amount',
        'approved_notes',
        'rejected_by',
        'rejected_at',
        'rejected_notes',
        // Direct Purchase fields
        'pr_type',
        'requires_po',
        'approval_request_date',
        'clause_number',
        'prepared_by_id',
        'io_number',
        'cost_center',
        'supplier_vendor_id',
        'reference_document',
        'completion_date',
        'completion_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'approval_request_date' => 'date',
        'completion_date' => 'datetime',
        'requires_po' => 'boolean',
    ];

    /**
     * Get the department that owns the purchase requisition.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the company that owns the purchase requisition.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who requested the purchase requisition.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user who requested the purchase requisition (alias for requester).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user who created the purchase requisition.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who created the purchase requisition (alias for creator).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get category options
     */
    public static function getCategoryOptions(): array
    {
        return [
            'premium_products' => 'สินค้าประเภทของพรี่เมี่ยม',
            'advertising_services' => 'จ้างโฆษณา',
        ];
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        $options = self::getCategoryOptions();
        return $options[$this->category] ?? $this->category;
    }

    public static function getWorkTypeOptions()
    {
        return [
            'buy' => 'ซื้อ',
            'hire' => 'จ้าง',
            'rent' => 'เช่า',
        ];
    }

    public function getWorkTypeLabelAttribute()
    {
        $options = self::getWorkTypeOptions();
        return $options[$this->work_type] ?? $this->work_type;
    }

    public static function getProcurementMethodOptions()
    {
        return [
            'agreement_price' => 'ตกลงราคา',
            'invitation_bid' => 'ประมูลโดยการประกาศเชิญ',
            'open_bid' => 'ประมูลโดยการประกาศเชิญชวนทั่วไป',
            'special_1' => 'พิเศษ ข้อ 1',
            'special_2' => 'พิเศษ ข้อ 2',
            'selection' => 'คัดเลือก',
        ];
    }

    public function getProcurementMethodLabelAttribute()
    {
        $options = self::getProcurementMethodOptions();
        return $options[$this->procurement_method] ?? $this->procurement_method;
    }

    /**
     * Get the user who approved the purchase requisition.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the purchase requisition.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the procurement committee member assigned to this PR.
     */
    public function procurementCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procurement_committee_id');
    }

    /**
     * Get the inspection committee member assigned to this PR.
     */
    public function inspectionCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspection_committee_id');
    }

    /**
     * Get the PR approver assigned to this PR.
     */
    public function prApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pr_approver_id');
    }

    /**
     * Get the other stakeholder assigned to this PR.
     */
    public function otherStakeholder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'other_stakeholder_id');
    }

    /**
     * Get the items for the purchase requisition.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    /**
     * Get the approvals for the purchase requisition.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionApproval::class);
    }

    /**
     * Get the attachments for the purchase requisition.
     */
    public function attachments(): HasMany
    {
        // Temporarily disabled - table may not exist in all databases
        return $this->hasMany(PurchaseRequisitionAttachment::class);
    }

    /**
     * Get the purchase orders for the purchase requisition.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Scope a query to only include purchase requisitions for a specific department.
     */
    public function scopeDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to only include purchase requisitions with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include purchase requisitions with a specific priority.
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include purchase requisitions created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('requester_id', $userId);
    }

    /**
     * Generate a unique PR number.
     */
    public static function generatePRNumber(): string
    {
        $prefix = 'PR';
        $year = date('Y');
        $month = date('m');
        
        $lastPR = self::where('pr_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('pr_number', 'desc')
            ->first();
        
        if ($lastPR) {
            $lastNumber = (int) substr($lastPR->pr_number, 8);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the supplier vendor for direct purchase
     */
    public function supplierVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier_vendor_id');
    }

    /**
     * Get the prepared by user for direct purchase
     */
    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    /**
     * Check if this PR requires a PO
     */
    public function requiresPO(): bool
    {
        return $this->pr_type === 'standard';
    }

    /**
     * Check if this is a direct purchase PR
     */
    public function isDirectPurchase(): bool
    {
        return in_array($this->pr_type, ['direct_small', 'direct_medium']);
    }

    /**
     * Get maximum amount allowed for PR type
     */
    public function getMaximumAmount(): float
    {
        switch ($this->pr_type) {
            case 'direct_small':
                return 10000;
            case 'direct_medium':
                return 100000;
            default:
                return PHP_FLOAT_MAX;
        }
    }

    /**
     * Validate if amount is within limit for PR type
     */
    public function validateAmountLimit(): bool
    {
        return $this->total_amount <= $this->getMaximumAmount();
    }

    /**
     * Get PR type label in Thai
     */
    public function getPrTypeLabelAttribute(): string
    {
        $labels = [
            'standard' => 'PR ปกติ',
            'direct_small' => 'จัดซื้อตรง ≤10,000 บาท',
            'direct_medium' => 'จัดซื้อตรง ≤100,000 บาท',
        ];
        
        return $labels[$this->pr_type] ?? $this->pr_type;
    }

    /**
     * Check if PR can be edited (for drag & drop functionality)
     */
    public function canEdit(): bool
    {
        // Can edit if status is draft, pending_approval, or approved but not closed/cancelled
        $editableStatuses = ['draft', 'pending_approval', 'approved'];
        return in_array($this->status, $editableStatuses);
    }

    /**
     * Complete direct purchase PR after approval
     */
    public function completeDirectPurchase($notes = null): void
    {
        if ($this->isDirectPurchase() && $this->status === 'approved') {
            $this->update([
                'status' => 'completed',
                'completion_date' => now(),
                'completion_notes' => $notes,
            ]);
        }
    }
} 