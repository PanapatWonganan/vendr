<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorScore extends Model
{
    protected $fillable = [
        'vendor_id',
        'company_id',
        'year',
        'quarter',
        'month',
        'total_score',
        'total_weighted_score',
        'total_po_value',
        'evaluation_count',
        'average_score',
        'weighted_average_score',
        'grade',
        'weighted_grade',
        'category_scores',
        'category_counts',
        'previous_score',
        'trend',
        'score_change',
        'last_evaluation_date',
        'last_evaluation_id',
        'evaluation_ids',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'total_score' => 'decimal:2',
        'total_weighted_score' => 'decimal:2',
        'total_po_value' => 'decimal:2',
        'evaluation_count' => 'integer',
        'average_score' => 'decimal:2',
        'weighted_average_score' => 'decimal:2',
        'previous_score' => 'decimal:2',
        'score_change' => 'decimal:2',
        'category_scores' => 'array',
        'category_counts' => 'array',
        'evaluation_ids' => 'array',
        'last_evaluation_date' => 'date',
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

    public function lastEvaluation(): BelongsTo
    {
        return $this->belongsTo(VendorEvaluation::class, 'last_evaluation_id');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForQuarter($query, $quarter)
    {
        return $query->where('quarter', $quarter);
    }

    public function scopeTopPerformers($query, $limit = 5)
    {
        return $query->orderBy('weighted_average_score', 'desc')->limit($limit);
    }

    public function scopeNeedImprovement($query)
    {
        return $query->whereIn('weighted_grade', ['C', 'D']);
    }

    // Methods
    public static function calculateGrade($score): string
    {
        if ($score >= 3.5) return 'A';
        if ($score >= 2.5) return 'B';
        if ($score >= 1.5) return 'C';
        return 'D';
    }
    
    // Accessors for consistent naming
    public function getCurrentScoreAttribute()
    {
        return $this->weighted_average_score ?? $this->average_score;
    }
    
    public function getCurrentGradeAttribute()
    {
        return $this->weighted_grade ?? $this->grade;
    }

    public function getGradeColorAttribute(): string
    {
        return match($this->weighted_grade ?? $this->grade) {
            'A' => 'success',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'danger',
            default => 'gray'
        };
    }

    public function getGradeDescriptionAttribute(): string
    {
        return match($this->weighted_grade ?? $this->grade) {
            'A' => 'ดีมาก',
            'B' => 'ดี',
            'C' => 'พอใช้',
            'D' => 'ควรปรับปรุง',
            default => 'ยังไม่ประเมิน'
        };
    }

    public function getTrendIconAttribute(): string
    {
        return match($this->trend) {
            'up' => '↑',
            'down' => '↓',
            'stable' => '→',
            default => ''
        };
    }

    public function getTrendColorAttribute(): string
    {
        return match($this->trend) {
            'up' => 'success',
            'down' => 'danger',
            'stable' => 'gray',
            default => 'gray'
        };
    }

    public function getFormattedScoreAttribute(): string
    {
        $score = $this->weighted_average_score ?? $this->average_score;
        if (!$score) return 'N/A';
        
        return number_format($score, 2) . '/4.00';
    }

    public function getFormattedGradeAttribute(): string
    {
        $grade = $this->weighted_grade ?? $this->grade;
        $score = $this->weighted_average_score ?? $this->average_score;
        
        if (!$grade) return 'N/A';
        
        return sprintf('%s (%s)', $grade, number_format($score, 2));
    }

    // Static method to find or create score record
    public static function findOrCreateForPeriod($vendorId, $companyId, $year, $quarter = null, $month = null)
    {
        return static::firstOrCreate([
            'vendor_id' => $vendorId,
            'company_id' => $companyId,
            'year' => $year,
            'quarter' => $quarter,
            'month' => $month,
        ]);
    }

    // Update trend based on previous score
    public function updateTrend()
    {
        $currentScore = $this->weighted_average_score ?? $this->average_score;
        
        if (!$currentScore || !$this->previous_score) {
            $this->trend = 'stable';
            $this->score_change = 0;
            return;
        }

        $change = $currentScore - $this->previous_score;
        
        if ($change > 0.1) {
            $this->trend = 'up';
        } elseif ($change < -0.1) {
            $this->trend = 'down';
        } else {
            $this->trend = 'stable';
        }
        
        $this->score_change = $change;
    }
}