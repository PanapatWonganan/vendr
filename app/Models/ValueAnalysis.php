<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValueAnalysis extends Model
{
    use HasFactory;

    protected $table = 'value_analysis';

    const AMENDMENT_TYPES = [
        'price_change' => 'ปรับราคา',
        'quantity_change' => 'ปรับจำนวน',
        'scope_change' => 'ปรับขอบเขตงาน',
        'item_change' => 'เพิ่ม/ลบรายการ',
        'general' => 'แก้ไขทั่วไป',
    ];

    protected $fillable = [
        'va_number',
        'parent_va_id',
        'revision_number',
        'amendment_type',
        'amendment_reason',
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
        'revision_number' => 'integer',
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

    public function items(): HasMany
    {
        return $this->hasMany(ValueAnalysisItem::class)->orderBy('line_number');
    }

    // Revision Relationships

    public function parentVa(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_va_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_va_id')->orderBy('revision_number');
    }

    public function isRevision(): bool
    {
        return $this->parent_va_id !== null;
    }

    public function getOriginal_va(): self
    {
        return $this->parent_va_id ? $this->parentVa : $this;
    }

    public function latestRevision(): ?self
    {
        return $this->revisions()->latest('revision_number')->first();
    }

    public function getLatestApprovedRevision(): ?self
    {
        return $this->revisions()->where('status', 'approved')->latest('revision_number')->first();
    }

    /**
     * Create a revision copy of this VA for amendment purposes.
     */
    public function createRevision(string $amendmentType, string $amendmentReason, int $createdBy): self
    {
        $originalVaId = $this->parent_va_id ?? $this->id;
        $originalVa = $this->parent_va_id ? $this->parentVa : $this;

        $nextRevision = self::where('parent_va_id', $originalVaId)
            ->max('revision_number') ?? $originalVa->revision_number;

        $revision = self::create([
            'va_number' => self::generateVANumber(),
            'parent_va_id' => $originalVaId,
            'revision_number' => $nextRevision + 1,
            'amendment_type' => $amendmentType,
            'amendment_reason' => $amendmentReason,
            'purchase_requisition_id' => $this->purchase_requisition_id,
            'work_type' => $this->work_type,
            'procurement_method' => $this->procurement_method,
            'procured_from' => $this->procured_from,
            'agreed_amount' => $this->agreed_amount,
            'total_budget' => $this->total_budget,
            'currency' => $this->currency,
            'analysis_objective' => $this->analysis_objective,
            'analysis_scope' => $this->analysis_scope,
            'evaluation_criteria' => $this->evaluation_criteria,
            'alternatives' => $this->alternatives,
            'comparison_matrix' => $this->comparison_matrix,
            'recommendations' => $this->recommendations,
            'conclusion' => $this->conclusion,
            'status' => 'draft',
            'created_by' => $createdBy,
        ]);

        // Copy items from current VA
        foreach ($this->items as $item) {
            $revision->items()->create([
                'purchase_requisition_item_id' => $item->purchase_requisition_item_id,
                'line_number' => $item->line_number,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_of_measure' => $item->unit_of_measure,
                'estimated_unit_price' => $item->agreed_unit_price,
                'estimated_amount' => $item->agreed_amount,
                'agreed_unit_price' => $item->agreed_unit_price,
                'agreed_amount' => $item->agreed_amount,
            ]);
        }

        return $revision;
    }

    /**
     * Get changes compared to the parent VA (for comparison display).
     */
    public function getChangesFromParent(): ?array
    {
        if (!$this->parent_va_id) {
            return null;
        }

        $parent = $this->parentVa;
        $changes = [];

        // Compare totals
        if ((float) $parent->agreed_amount !== (float) $this->agreed_amount) {
            $changes['agreed_amount'] = [
                'old' => $parent->agreed_amount,
                'new' => $this->agreed_amount,
                'diff' => $this->agreed_amount - $parent->agreed_amount,
            ];
        }

        // Compare items
        $parentItems = $parent->items->keyBy('line_number');
        $currentItems = $this->items->keyBy('line_number');

        $itemChanges = [];
        foreach ($currentItems as $lineNumber => $item) {
            $parentItem = $parentItems->get($lineNumber);
            if (!$parentItem) {
                $itemChanges[] = ['type' => 'added', 'item' => $item];
            } else {
                $diffs = [];
                if ((float) $parentItem->agreed_unit_price !== (float) $item->agreed_unit_price) {
                    $diffs['agreed_unit_price'] = ['old' => $parentItem->agreed_unit_price, 'new' => $item->agreed_unit_price];
                }
                if ((float) $parentItem->quantity !== (float) $item->quantity) {
                    $diffs['quantity'] = ['old' => $parentItem->quantity, 'new' => $item->quantity];
                }
                if ($parentItem->description !== $item->description) {
                    $diffs['description'] = ['old' => $parentItem->description, 'new' => $item->description];
                }
                if (!empty($diffs)) {
                    $itemChanges[] = ['type' => 'changed', 'line_number' => $lineNumber, 'diffs' => $diffs];
                }
            }
        }

        // Detect removed items
        foreach ($parentItems as $lineNumber => $item) {
            if (!$currentItems->has($lineNumber)) {
                $itemChanges[] = ['type' => 'removed', 'item' => $item];
            }
        }

        if (!empty($itemChanges)) {
            $changes['items'] = $itemChanges;
        }

        return empty($changes) ? null : $changes;
    }

    public function getRevisionLabelAttribute(): string
    {
        if ($this->revision_number <= 1 && !$this->parent_va_id) {
            return '';
        }
        return 'Rev.' . $this->revision_number;
    }

    public function getAmendmentTypeLabelAttribute(): string
    {
        return self::AMENDMENT_TYPES[$this->amendment_type] ?? $this->amendment_type ?? '';
    }

    /**
     * Recalculate agreed_amount from items
     */
    public function recalculateFromItems(): void
    {
        $this->update([
            'agreed_amount' => $this->items()->sum('agreed_amount'),
        ]);
    }

    /**
     * Copy items from the linked PR and pre-fill estimated prices
     */
    public function copyItemsFromPR(): void
    {
        $pr = $this->purchaseRequisition;
        if (!$pr) return;

        foreach ($pr->items as $index => $prItem) {
            $this->items()->create([
                'purchase_requisition_item_id' => $prItem->id,
                'line_number' => $index + 1,
                'item_code' => $prItem->item_code,
                'description' => $prItem->description,
                'quantity' => $prItem->quantity,
                'unit_of_measure' => $prItem->unit_of_measure,
                'estimated_unit_price' => $prItem->estimated_unit_price ?? 0,
                'estimated_amount' => $prItem->estimated_amount ?? ($prItem->quantity * ($prItem->estimated_unit_price ?? 0)),
                'agreed_unit_price' => $prItem->estimated_unit_price ?? 0, // Default to PR price
                'agreed_amount' => $prItem->estimated_amount ?? ($prItem->quantity * ($prItem->estimated_unit_price ?? 0)),
            ]);
        }
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
