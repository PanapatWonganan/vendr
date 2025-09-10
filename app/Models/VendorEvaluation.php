<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PurchaseOrder;

class VendorEvaluation extends Model
{
    protected $fillable = [
        'vendor_id',
        'company_id',
        'evaluator_id',
        'purchase_order_id',
        'payment_term_number',
        'payment_term_description',
        'project_name',
        'committee_members',
        'evaluation_period',
        'evaluation_date',
        'period_start',
        'period_end',
        'overall_score',
        'total_criteria',
        'applicable_criteria',
        'status',
        'approved_by',
        'approved_at',
        'general_comments',
        'recommendations',
        'areas_for_improvement',
        'rejection_reason',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'overall_score' => 'decimal:2',
        'committee_members' => 'array',
        'payment_term_number' => 'integer',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function evaluationItems(): HasMany
    {
        return $this->hasMany(VendorEvaluationItem::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('evaluation_period', $period);
    }

    // Methods
    public function calculateOverallScore()
    {
        $applicableItems = $this->evaluationItems()->where('is_applicable', true)->get();
        
        if ($applicableItems->isEmpty()) {
            $this->update([
                'overall_score' => null,
                'applicable_criteria' => 0
            ]);
            return null;
        }

        $totalScore = $applicableItems->sum('score');
        $maxPossibleScore = $applicableItems->count() * 4; // Max score is 4
        
        $overallScore = ($totalScore / $maxPossibleScore) * 100;
        
        $this->update([
            'overall_score' => $overallScore,
            'applicable_criteria' => $applicableItems->count(),
            'total_criteria' => $this->evaluationItems()->count()
        ]);

        return $overallScore;
    }

    public function getScoreGradeAttribute()
    {
        if (!$this->overall_score) return 'N/A';
        
        // Convert percentage to 4-point scale
        $score = ($this->overall_score / 100) * 4;
        
        if ($score >= 3.5) return 'A';
        if ($score >= 2.5) return 'B';
        if ($score >= 1.5) return 'C';
        return 'D';
    }
    
    public function getScoreGradeDetailAttribute()
    {
        if (!$this->overall_score) return 'ยังไม่ประเมิน';
        
        // Convert percentage to 4-point scale
        $score = ($this->overall_score / 100) * 4;
        $scoreFormatted = number_format($score, 2);
        
        if ($score >= 3.5) return "A (คะแนน $scoreFormatted) - ดีมาก";
        if ($score >= 2.5) return "B (คะแนน $scoreFormatted) - ดี";
        if ($score >= 1.5) return "C (คะแนน $scoreFormatted) - พอใช้";
        return "D (คะแนน $scoreFormatted) - ควรปรับปรุง";
    }
    
    public function getScoreGradeColorAttribute()
    {
        $grade = $this->score_grade;
        
        return match($grade) {
            'A' => 'success',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'danger',
            default => 'gray'
        };
    }
    
    public function getAverageScoreAttribute()
    {
        if (!$this->overall_score) return null;
        
        // Convert percentage to 4-point scale
        return round(($this->overall_score / 100) * 4, 2);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
}
