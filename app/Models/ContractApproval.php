<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractApproval extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contract_number',
        'contract_title',
        'description',
        'vendor_name',
        'contract_value',
        'currency',
        'contract_date',
        'start_date',
        'end_date',
        'contract_type',
        'status',
        'department_id',
        'uploaded_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'priority',
        'project_code',
        'budget_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'contract_value' => 'decimal:2',
        'contract_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Generate contract number automatically
     */
    public static function generateContractNumber(): string
    {
        $date = date('Ymd');
        $lastContract = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "CT-{$date}-{$newNumber}";
    }

    /**
     * Get the department that owns the contract.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who uploaded the contract.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who reviewed the contract.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the contract files.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ContractFile::class);
    }

    /**
     * Get the main contract file.
     */
    public function contractFile()
    {
        return $this->files()->where('file_category', 'contract')->first();
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'under_review' => 'bg-info',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status text in Thai
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอตรวจสอบ',
            'under_review' => 'กำลังตรวจสอบ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get contract type text in Thai
     */
    public function getContractTypeTextAttribute(): string
    {
        return match ($this->contract_type) {
            'purchase' => 'จัดซื้อ',
            'service' => 'จัดจ้าง',
            'rental' => 'เช่า',
            'maintenance' => 'บำรุงรักษา',
            'other' => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get priority text in Thai
     */
    public function getPriorityTextAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง',
            'urgent' => 'เร่งด่วน',
            default => 'ปานกลาง',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'bg-secondary',
            'medium' => 'bg-primary',
            'high' => 'bg-warning',
            'urgent' => 'bg-danger',
            default => 'bg-primary',
        };
    }
}
