<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalLevel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'level',
        'approval_type',
        'threshold_amount',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'level' => 'integer',
        'threshold_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the purchase requisition approvals for this approval level.
     */
    public function purchaseRequisitionApprovals(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionApproval::class);
    }

    /**
     * Scope a query to only include active approval levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by level.
     */
    public function scopeOrderByLevel($query, $direction = 'asc')
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * Scope a query to filter by approval type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('approval_type', $type);
    }
}
