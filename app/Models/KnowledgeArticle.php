<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeArticle extends Model
{
    protected $fillable = [
        'title',
        'content',
        'category',
        'type',
        'youtube_url',
        'video_duration',
        'file_path',
        'is_published',
        'views_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
    ];

    public const TYPE_DOCUMENT = 'document';
    public const TYPE_VIDEO = 'video';

    public const TYPES = [
        self::TYPE_DOCUMENT => 'เอกสาร',
        self::TYPE_VIDEO => 'วิดีโอ',
    ];

    public const CATEGORIES = [
        'getting-started' => 'เริ่มต้นใช้งาน',
        'purchase-requisition' => 'ใบขอซื้อ',
        'purchase-order' => 'ใบสั่งซื้อ',
        'goods-receipt' => 'ใบรับของ',
        'vendor-management' => 'จัดการผู้ขาย',
        'reports' => 'รายงาน',
        'administration' => 'การจัดการระบบ',
        'troubleshooting' => 'แก้ไขปัญหา',
        'general' => 'ทั่วไป',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getYouTubeEmbedUrlAttribute(): ?string
    {
        if (!$this->youtube_url) {
            return null;
        }

        // Convert YouTube URL to embed URL
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        if (preg_match($pattern, $this->youtube_url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return null;
    }
    
    public function getYouTubeIdAttribute(): ?string
    {
        if (!$this->youtube_url) {
            return null;
        }

        // Extract YouTube video ID
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        if (preg_match($pattern, $this->youtube_url, $matches)) {
            return $matches[1];
        }

        return null;
    }
    
    public function getYouTubeThumbnailAttribute(): ?string
    {
        if (!$this->youtube_id) {
            return null;
        }

        // Return high quality thumbnail
        // Options: default, mqdefault, hqdefault, sddefault, maxresdefault
        return "https://img.youtube.com/vi/{$this->youtube_id}/maxresdefault.jpg";
    }
    
    public function getYouTubeThumbnailMediumAttribute(): ?string
    {
        if (!$this->youtube_id) {
            return null;
        }

        // Return medium quality thumbnail
        return "https://img.youtube.com/vi/{$this->youtube_id}/hqdefault.jpg";
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
