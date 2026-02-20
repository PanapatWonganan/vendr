<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAssessment extends Model
{
    protected $fillable = [
        'vendor_id',
        'company_id',
        'assessed_by',
        'tax_id',

        // DBD Data
        'dbd_status',
        'dbd_entity_type',
        'dbd_registered_capital',
        'dbd_name_th',
        'dbd_name_en',
        'dbd_address',
        'dbd_registration_date',
        'dbd_objectives',
        'dbd_raw_data',
        'dbd_fetched_at',

        // AI Analysis
        'risk_score',
        'risk_level',
        'ai_summary',
        'ai_risk_factors',
        'ai_strengths',
        'ai_recommendations',
        'dimension_scores',
        'ai_raw_response',
        'ai_model_used',
        'ai_analyzed_at',

        // Combined
        'overall_risk_score',
        'overall_risk_level',
        'assessment_status',
        'error_message',
    ];

    protected $casts = [
        'dbd_registered_capital' => 'decimal:2',
        'dbd_registration_date' => 'date',
        'dbd_objectives' => 'array',
        'dbd_raw_data' => 'array',
        'dbd_fetched_at' => 'datetime',
        'risk_score' => 'integer',
        'ai_risk_factors' => 'array',
        'ai_strengths' => 'array',
        'ai_recommendations' => 'array',
        'dimension_scores' => 'array',
        'ai_analyzed_at' => 'datetime',
        'overall_risk_score' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_DBD_FETCHED = 'dbd_fetched';
    const STATUS_ANALYZED = 'analyzed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Risk level constants
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    // Accessors
    public function getRiskLevelLabelAttribute(): string
    {
        return match ($this->overall_risk_level ?? $this->risk_level) {
            self::RISK_LOW => 'ความเสี่ยงต่ำ',
            self::RISK_MEDIUM => 'ความเสี่ยงปานกลาง',
            self::RISK_HIGH => 'ความเสี่ยงสูง',
            self::RISK_CRITICAL => 'ความเสี่ยงวิกฤต',
            default => 'ยังไม่ประเมิน',
        };
    }

    public function getRiskLevelColorAttribute(): string
    {
        return match ($this->overall_risk_level ?? $this->risk_level) {
            self::RISK_LOW => 'success',
            self::RISK_MEDIUM => 'warning',
            self::RISK_HIGH => 'danger',
            self::RISK_CRITICAL => 'danger',
            default => 'gray',
        };
    }

    public function getRiskLevelIconAttribute(): string
    {
        return match ($this->overall_risk_level ?? $this->risk_level) {
            self::RISK_LOW => 'heroicon-o-shield-check',
            self::RISK_MEDIUM => 'heroicon-o-exclamation-triangle',
            self::RISK_HIGH => 'heroicon-o-exclamation-circle',
            self::RISK_CRITICAL => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->assessment_status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_DBD_FETCHED => 'ดึงข้อมูล DBD แล้ว',
            self::STATUS_ANALYZED => 'AI วิเคราะห์แล้ว',
            self::STATUS_COMPLETED => 'เสร็จสมบูรณ์',
            self::STATUS_FAILED => 'ล้มเหลว',
            default => 'ไม่ระบุ',
        };
    }

    public function getDbdCompanyAgeAttribute(): ?string
    {
        if (!$this->dbd_registration_date) {
            return null;
        }

        $years = $this->dbd_registration_date->diffInYears(now());
        $months = $this->dbd_registration_date->diffInMonths(now()) % 12;

        if ($years > 0) {
            return "{$years} ปี {$months} เดือน";
        }

        return "{$months} เดือน";
    }

    public function getDbdStatusColorAttribute(): string
    {
        $status = mb_strtolower($this->dbd_status ?? '');

        if (str_contains($status, 'ดำเนินกิจการ') || str_contains($status, 'ยังดำเนินกิจการ')) {
            return 'success';
        }
        if (str_contains($status, 'เลิก') || str_contains($status, 'ล้มละลาย')) {
            return 'danger';
        }
        if (str_contains($status, 'ร้าง') || str_contains($status, 'ถูกขีดชื่อ')) {
            return 'warning';
        }

        return 'gray';
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('assessment_status', self::STATUS_COMPLETED);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public function isCompleted(): bool
    {
        return $this->assessment_status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->assessment_status === self::STATUS_FAILED;
    }

    public function hasDbdData(): bool
    {
        return $this->dbd_fetched_at !== null;
    }

    public function hasAiAnalysis(): bool
    {
        return $this->ai_analyzed_at !== null;
    }
}
