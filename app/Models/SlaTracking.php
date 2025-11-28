<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaTracking extends Model
{
    protected $fillable = [
        'company_id',
        'purchase_requisition_id',
        'purchase_order_id',
        'stage',
        'procurement_method',
        'sla_standard_days',
        'start_date',
        'end_date',
        'actual_working_days',
        'sla_percentage',
        'sla_grade',
        'days_difference',
        'status',
        'remarks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sla_percentage' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Helper Methods
    public function getGradeColor(): string
    {
        return match($this->sla_grade) {
            'S' => 'success',
            'A' => 'primary',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'danger',
            'F' => 'danger',
            default => 'secondary',
        };
    }

    public function getGradeLabel(): string
    {
        return match($this->sla_grade) {
            'S' => 'Excellent',
            'A' => 'Very Good',
            'B' => 'Good',
            'C' => 'Average',
            'D' => 'Below Average',
            'F' => 'Fail',
            default => 'N/A',
        };
    }

    public function getStageName(): string
    {
        return match($this->stage) {
            'pr_submission_to_approval' => 'PR Submission → Approval',
            'pr_approval_to_po_creation' => 'PR Approval → PO Creation',
            'po_creation_to_approval' => 'PO Creation → Approval',
            'full_cycle' => 'Full Cycle (PR → PO Approved)',
            default => $this->stage,
        };
    }
}
