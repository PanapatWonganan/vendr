<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GoodsReceiptAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileIconAttribute(): string
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'pdf' => '📄',
            'jpg', 'jpeg', 'png', 'gif' => '🖼️',
            'doc', 'docx' => '📝',
            'xls', 'xlsx' => '📊',
            'zip', 'rar' => '🗜️',
            default => '📎'
        };
    }

    public function canPreview(): bool
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
    }

    public function delete(): bool
    {
        // ลบไฟล์จาก storage ก่อน
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
        
        // ลบ record จาก database
        return parent::delete();
    }
}
