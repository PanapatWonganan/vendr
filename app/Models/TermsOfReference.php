<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermsOfReference extends BaseModel
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_AMENDED = 'amended';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_REVIEWING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_AMENDED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'terms_of_references';

    protected $fillable = [
        'tor_number',
        'company_id',
        'department_id',
        'title',
        'project_name',
        'project_code',
        'tor_type',
        'form_category',
        'work_type',
        'procurement_method',
        'category',
        'background',
        'objectives',
        'scope_of_work',
        'deliverables',
        'specifications',
        'qualification_requirements',
        'evaluation_criteria',
        'payment_terms',
        'warranty_requirements',
        'penalty_clause',
        'start_date',
        'end_date',
        'duration_days',
        'budget_estimate',
        'currency',
        'budget_code',
        'status',
        'priority',
        'procurement_committee_id',
        'inspection_committee_id',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'revision_number',
        'parent_tor_id',
        'amendment_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_estimate' => 'decimal:2',
        'evaluation_criteria' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'duration_days' => 'integer',
        'revision_number' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function procurementCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procurement_committee_id');
    }

    public function inspectionCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspection_committee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TorItem::class, 'tor_id')->orderBy('item_number');
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(TorApprovalHistory::class, 'tor_id')->orderBy('performed_at', 'desc');
    }

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class, 'tor_id');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class, 'tor_id');
    }

    public function attachments()
    {
        return $this->morphMany(ProcurementAttachment::class, 'attachable');
    }

    public function parentTor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tor_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_tor_id')->orderBy('revision_number');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'reviewing']);
    }

    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ─── Number Generation ───────────────────────────────────────

    public static function generateTorNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $prefix = "TOR-{$year}{$month}{$day}";
        $companyId = session('company_id');

        if (!$companyId) {
            throw new \RuntimeException('Cannot generate TOR number without company context.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $companyId) {
            $lastTor = static::where('company_id', $companyId)
                ->where('tor_number', 'like', "{$prefix}%")
                ->orderBy('tor_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastTor) {
                $lastNumber = (int) substr($lastTor->tor_number, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    // ─── Workflow Methods ────────────────────────────────────────

    public function submit(User $user): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $fromStatus = $this->status;
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $this->recordHistory('submitted', $user, $fromStatus, 'submitted');
        return true;
    }

    public function approve(User $user, ?string $comments = null): bool
    {
        if (!in_array($this->status, ['submitted', 'reviewing'])) {
            return false;
        }

        $fromStatus = $this->status;
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $this->recordHistory('approved', $user, $fromStatus, 'approved', $comments);
        return true;
    }

    public function reject(User $user, string $reason, ?string $comments = null): bool
    {
        if (!in_array($this->status, ['submitted', 'reviewing'])) {
            return false;
        }

        $fromStatus = $this->status;
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $user->id,
            'rejection_reason' => $reason,
        ]);

        $this->recordHistory('rejected', $user, $fromStatus, 'rejected', $comments ?? $reason);
        return true;
    }

    public function returnToDraft(User $user, ?string $comments = null): bool
    {
        if ($this->status !== 'rejected') {
            return false;
        }

        $fromStatus = $this->status;
        $this->update(['status' => 'draft']);
        $this->recordHistory('returned', $user, $fromStatus, 'draft', $comments);
        return true;
    }

    public function cancel(User $user, ?string $comments = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $fromStatus = $this->status;
        $this->update(['status' => 'cancelled']);
        $this->recordHistory('cancelled', $user, $fromStatus, 'cancelled', $comments);
        return true;
    }

    public function createRevision(User $user, string $amendmentReason): self
    {
        $originalTorId = $this->parent_tor_id ?? $this->id;
        $originalTor = $this->parent_tor_id ? $this->parentTor : $this;

        $nextRevision = self::where('parent_tor_id', $originalTorId)
            ->max('revision_number') ?? $originalTor->revision_number;

        $revision = self::create([
            'tor_number' => self::generateTorNumber(),
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'title' => $this->title,
            'project_name' => $this->project_name,
            'project_code' => $this->project_code,
            'tor_type' => $this->tor_type,
            'form_category' => $this->form_category,
            'work_type' => $this->work_type,
            'procurement_method' => $this->procurement_method,
            'category' => $this->category,
            'background' => $this->background,
            'objectives' => $this->objectives,
            'scope_of_work' => $this->scope_of_work,
            'deliverables' => $this->deliverables,
            'specifications' => $this->specifications,
            'qualification_requirements' => $this->qualification_requirements,
            'evaluation_criteria' => $this->evaluation_criteria,
            'payment_terms' => $this->payment_terms,
            'warranty_requirements' => $this->warranty_requirements,
            'penalty_clause' => $this->penalty_clause,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration_days' => $this->duration_days,
            'budget_estimate' => $this->budget_estimate,
            'currency' => $this->currency,
            'budget_code' => $this->budget_code,
            'status' => 'draft',
            'priority' => $this->priority,
            'procurement_committee_id' => $this->procurement_committee_id,
            'inspection_committee_id' => $this->inspection_committee_id,
            'revision_number' => $nextRevision + 1,
            'parent_tor_id' => $originalTorId,
            'amendment_reason' => $amendmentReason,
            'created_by' => $user->id,
        ]);

        // Copy items
        foreach ($this->items as $item) {
            $revision->items()->create([
                'item_number' => $item->item_number,
                'description' => $item->description,
                'specifications' => $item->specifications,
                'quantity' => $item->quantity,
                'unit_of_measure' => $item->unit_of_measure,
                'estimated_unit_price' => $item->estimated_unit_price,
                'estimated_total_price' => $item->estimated_total_price,
                'delivery_location' => $item->delivery_location,
                'required_date' => $item->required_date,
                'remarks' => $item->remarks,
            ]);
        }

        // Update original status
        $fromStatus = $this->status;
        $this->update(['status' => 'amended']);
        $this->recordHistory('amended', $user, $fromStatus, 'amended', $amendmentReason);

        return $revision;
    }

    public function recordHistory(string $action, User $user, ?string $fromStatus = null, ?string $toStatus = null, ?string $comments = null): void
    {
        $this->approvalHistory()->create([
            'action' => $action,
            'performed_by' => $user->id,
            'performed_at' => now(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comments' => $comments,
        ]);
    }

    // ─── Business Logic ──────────────────────────────────────────

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft'
            && !empty($this->title)
            && !empty($this->scope_of_work);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'reviewing']);
    }

    public function isRevision(): bool
    {
        return $this->parent_tor_id !== null;
    }

    // ─── TOR to PR Conversion ────────────────────────────────────

    public function convertToPrData(): array
    {
        return [
            'tor_id' => $this->id,
            'title' => $this->title,
            'department_id' => $this->department_id,
            'form_category' => $this->form_category,
            'work_type' => $this->work_type,
            'procurement_method' => $this->procurement_method,
            'category' => $this->category,
            'procurement_budget' => $this->budget_estimate,
            'budget_code' => $this->budget_code,
            'description' => $this->scope_of_work,
            'purpose' => $this->objectives,
            'justification' => $this->background,
            'required_date' => $this->start_date,
            'expected_delivery_date' => $this->end_date,
            'procurement_committee_id' => $this->procurement_committee_id,
            'inspection_committee_id' => $this->inspection_committee_id,
            'currency' => $this->currency,
        ];
    }

    public function convertItemsToPrItems(): array
    {
        return $this->items->map(function ($item) {
            return [
                'description' => $item->description,
                'specification' => $item->specifications,
                'quantity' => $item->quantity,
                'unit_of_measure' => $item->unit_of_measure,
                'estimated_unit_price' => $item->estimated_unit_price,
                'estimated_amount' => $item->estimated_total_price,
                'required_date' => $item->required_date,
                'remarks' => $item->remarks,
            ];
        })->toArray();
    }

    // ─── Option Methods (reuse from PR) ──────────────────────────

    public static function getTorTypeOptions(): array
    {
        return [
            'goods' => 'พัสดุ/วัสดุ',
            'services' => 'บริการ',
            'construction' => 'งานก่อสร้าง',
            'consulting' => 'งานที่ปรึกษา',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'ร่าง',
            'submitted' => 'ส่งพิจารณา',
            'reviewing' => 'กำลังพิจารณา',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'amended' => 'แก้ไข',
            'cancelled' => 'ยกเลิก',
            'expired' => 'หมดอายุ',
        ];
    }

    public static function getCategoryOptions(): array
    {
        return PurchaseRequisition::getCategoryOptions();
    }

    public static function getProcurementMethodOptions(): array
    {
        return PurchaseRequisition::getProcurementMethodOptions();
    }

    public static function getFormCategoryOptions(): array
    {
        return PurchaseRequisition::getFormCategoryOptions();
    }

    public static function getWorkTypeOptions(): array
    {
        return PurchaseRequisition::getWorkTypeOptions();
    }

    public static function getPriorityOptions(): array
    {
        return [
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'urgent' => 'เร่งด่วน',
        ];
    }

    // ─── Display Attributes ──────────────────────────────────────

    public function getStatusTextAttribute(): string
    {
        $options = self::getStatusOptions();
        return $options[$this->status] ?? $this->status ?? 'ไม่ระบุ';
    }

    public function getTorTypeLabelAttribute(): string
    {
        $options = self::getTorTypeOptions();
        return $options[$this->tor_type] ?? $this->tor_type ?? 'ไม่ระบุ';
    }

    public function getRevisionLabelAttribute(): string
    {
        if ($this->revision_number <= 0 && !$this->parent_tor_id) {
            return '';
        }
        return 'Rev.' . $this->revision_number;
    }
}
