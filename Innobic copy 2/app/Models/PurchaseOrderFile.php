<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderFile extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'file_category',
        'description',
        'uploaded_by',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper Methods
    public function getFileCategoryTextAttribute(): string
    {
        return match($this->file_category) {
            'po_document' => 'เอกสาร PO',
            'quotation' => 'ใบเสนอราคา',
            'specification' => 'รายละเอียดสินค้า',
            'attachment' => 'เอกสารแนบ',
            'delivery_note' => 'ใบส่งของ',
            'invoice' => 'ใบแจ้งหนี้',
            'other' => 'อื่นๆ',
            default => $this->file_category,
        };
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function isPdf(): bool
    {
        return str_contains($this->file_type, 'pdf');
    }

    public function isImage(): bool
    {
        return str_contains($this->file_type, 'image');
    }

    public function getFileUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
        return $this->delete();
    }
}
