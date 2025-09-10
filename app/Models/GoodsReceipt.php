<?php

namespace App\Models;

use App\Events\GoodsReceiptCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class GoodsReceipt extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'gr_number',
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'inspection_committee_id',
        'receipt_date',
        'delivery_milestone',
        'milestone_description',
        'milestone_percentage',
        'inspection_status',
        'inspection_notes',
        'delivery_note_number',
        'notes',
        'rejection_reason',
        'status',
        'received_by',
        'updated_by',
        'carrier',
        'tracking_number',
        'documents',
        'quality_check_notes',
        'is_quality_checked',
        'quality_checked_by',
        'quality_checked_at',
        'committee_notified_at',
        'reminder_sent_at',
        'reviewed_by',
        'reviewed_at',
        'created_by'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'committee_notified_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'quality_checked_at' => 'datetime',
        'milestone_percentage' => 'decimal:2',
        'is_quality_checked' => 'boolean',
        'documents' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RETURNED = 'returned';
    const STATUS_PARTIALLY_RETURNED = 'partially_returned';
    const STATUS_CANCELLED = 'cancelled';

    const INSPECTION_PENDING = 'pending';
    const INSPECTION_PASSED = 'passed';
    const INSPECTION_FAILED = 'failed';
    const INSPECTION_PARTIAL = 'partial';

    protected static function boot()
    {
        parent::boot();

        static::created(function ($goodsReceipt) {
            // Generate GR number if not already set
            if (!$goodsReceipt->gr_number) {
                $goodsReceipt->gr_number = $goodsReceipt->generateReceiptNumber();
                $goodsReceipt->saveQuietly();
            }

            // Set created_by if not already set
            if (!$goodsReceipt->created_by && Auth::check()) {
                $goodsReceipt->created_by = Auth::id();
                $goodsReceipt->saveQuietly();
            }

            // Dispatch event
            $creator = Auth::user() ?? User::find($goodsReceipt->created_by);
            if ($creator) {
                GoodsReceiptCreated::dispatch($goodsReceipt, $creator);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function inspectionCommittee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspection_committee_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function qualityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GoodsReceiptDocument::class);
    }

    public function paymentMilestones(): HasMany
    {
        return $this->hasMany(PaymentMilestone::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(\App\Models\GoodsReceiptAttachment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'แบบร่าง',
            self::STATUS_COMPLETED => 'เสร็จสมบูรณ์',
            self::STATUS_RETURNED => 'ส่งคืน',
            self::STATUS_PARTIALLY_RETURNED => 'ส่งคืนบางส่วน',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_RETURNED => 'bg-warning',
            self::STATUS_PARTIALLY_RETURNED => 'bg-info',
            self::STATUS_CANCELLED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getInspectionStatusLabelAttribute()
    {
        return match ($this->inspection_status) {
            self::INSPECTION_PENDING => 'รอการตรวจสอบ',
            self::INSPECTION_PASSED => 'ผ่านการตรวจสอบ',
            self::INSPECTION_FAILED => 'ไม่ผ่านการตรวจสอบ',
            self::INSPECTION_PARTIAL => 'ผ่านบางส่วน',
            default => $this->inspection_status,
        };
    }

    public function getInspectionStatusBadgeClassAttribute()
    {
        return match ($this->inspection_status) {
            self::INSPECTION_PENDING => 'bg-warning',
            self::INSPECTION_PASSED => 'bg-success',
            self::INSPECTION_FAILED => 'bg-danger',
            self::INSPECTION_PARTIAL => 'bg-info',
            default => 'bg-secondary',
        };
    }

    public function generateReceiptNumber()
    {
        $prefix = 'GR';
        $year = date('Y');
        $month = date('m');
        
        $lastReceipt = self::where('gr_number', 'like', "$prefix$year$month%")
            ->orderBy('gr_number', 'desc')
            ->first();
        
        if ($lastReceipt) {
            $lastNumber = intval(substr($lastReceipt->gr_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf("%s%s%s%04d", $prefix, $year, $month, $newNumber);
    }

    public function canEdit()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function canDelete()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canSubmitForReview()
    {
        return $this->status === self::STATUS_DRAFT && $this->items->count() > 0;
    }

    public function canApprove()
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function canReject()
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }
}