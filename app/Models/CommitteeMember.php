<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'user_id',
        'role',
        'assigned_date',
        'is_active'
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'is_active' => 'boolean',
    ];

    const ROLE_CHAIRMAN = 'chairman';
    const ROLE_MEMBER = 'member';
    const ROLE_SECRETARY = 'secretary';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function getRoleLabelAttribute()
    {
        return match ($this->role) {
            self::ROLE_CHAIRMAN => 'ประธานกรรมการ',
            self::ROLE_MEMBER => 'กรรมการ',
            self::ROLE_SECRETARY => 'เลขานุการ',
            default => $this->role,
        };
    }

    public function getRoleBadgeClassAttribute()
    {
        return match ($this->role) {
            self::ROLE_CHAIRMAN => 'bg-primary',
            self::ROLE_MEMBER => 'bg-info',
            self::ROLE_SECRETARY => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function isChairman()
    {
        return $this->role === self::ROLE_CHAIRMAN;
    }

    public function isMember()
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function isSecretary()
    {
        return $this->role === self::ROLE_SECRETARY;
    }
}