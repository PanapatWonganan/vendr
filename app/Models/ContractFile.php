<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contract_approval_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'file_category',
        'description',
        'uploaded_by',
    ];

    /**
     * Get the contract approval that owns the file.
     */
    public function contractApproval(): BelongsTo
    {
        return $this->belongsTo(ContractApproval::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
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

    /**
     * Get file category text in Thai
     */
    public function getFileCategoryTextAttribute(): string
    {
        return match ($this->file_category) {
            'contract' => 'ไฟล์สัญญาหลัก',
            'attachment' => 'เอกสารแนบ',
            'amendment' => 'เอกสารแก้ไขเพิ่มเติม',
            'approval' => 'เอกสารอนุมัติ',
            'other' => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)) === 'pdf';
    }
}
