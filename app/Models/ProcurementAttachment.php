<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementAttachment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'company_id',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'category',
        'description',
        'uploaded_by',
        'is_public',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function getFileTypeAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION)) ?: 'Unknown';
    }

    public static function getCategories(): array
    {
        return [
            'specification' => 'Technical Specification',
            'quotation' => 'Quotation',
            'proposal' => 'Proposal',
            'contract' => 'Contract',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'delivery_note' => 'Delivery Note',
            'inspection_report' => 'Inspection Report',
            'approval_document' => 'Approval Document',
            'other' => 'Other',
        ];
    }
}