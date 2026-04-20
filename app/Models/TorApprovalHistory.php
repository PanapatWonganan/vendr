<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'tor_approval_history';

    protected $fillable = [
        'tor_id',
        'action',
        'performed_by',
        'performed_at',
        'from_status',
        'to_status',
        'comments',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function termsOfReference(): BelongsTo
    {
        return $this->belongsTo(TermsOfReference::class, 'tor_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submitted' => 'ส่งพิจารณา',
            'reviewed' => 'ตรวจสอบ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'returned' => 'ส่งกลับแก้ไข',
            'cancelled' => 'ยกเลิก',
            'amended' => 'แก้ไข',
            default => $this->action,
        };
    }
}
