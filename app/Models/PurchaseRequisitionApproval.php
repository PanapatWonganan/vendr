<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionApproval extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_requisition_id',
        'user_id',
        'approval_level_id',
        'status',
        'comments',
        'approved_at',
        'rejected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the purchase requisition that owns the approval.
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    /**
     * Get the user who approved or rejected the purchase requisition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the approval level for this approval.
     */
    public function approvalLevel(): BelongsTo
    {
        return $this->belongsTo(ApprovalLevel::class);
    }

    /**
     * Approve the purchase requisition.
     */
    public function approve(string $comments = null): bool
    {
        $this->status = 'approved';
        $this->comments = $comments;
        $this->approved_at = now();
        $this->rejected_at = null;
        
        return $this->save();
    }

    /**
     * Reject the purchase requisition.
     */
    public function reject(string $comments): bool
    {
        if (empty($comments)) {
            return false;
        }
        
        $this->status = 'rejected';
        $this->comments = $comments;
        $this->rejected_at = now();
        $this->approved_at = null;
        
        return $this->save();
    }

    /**
     * Scope a query to only include pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
} 