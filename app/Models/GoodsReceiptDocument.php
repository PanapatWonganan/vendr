<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GoodsReceiptDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'file_type',
        'uploaded_by',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    const TYPE_DELIVERY_NOTE = 'delivery_note';
    const TYPE_INVOICE = 'invoice';
    const TYPE_INSPECTION_REPORT = 'inspection_report';
    const TYPE_OTHER = 'other';

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDocumentTypeLabelAttribute()
    {
        return match ($this->document_type) {
            self::TYPE_DELIVERY_NOTE => 'ใบส่งของ',
            self::TYPE_INVOICE => 'ใบแจ้งหนี้',
            self::TYPE_INSPECTION_REPORT => 'รายงานการตรวจสอบ',
            self::TYPE_OTHER => 'อื่นๆ',
            default => $this->document_type,
        };
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getFileIconAttribute()
    {
        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
        
        return match(strtolower($extension)) {
            'pdf' => 'fa-file-pdf text-danger',
            'doc', 'docx' => 'fa-file-word text-primary',
            'xls', 'xlsx' => 'fa-file-excel text-success',
            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-info',
            'zip', 'rar' => 'fa-file-archive text-warning',
            default => 'fa-file text-secondary',
        };
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }
}