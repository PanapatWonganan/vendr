<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'company_id',
        'company_name',
        'tax_id',
        'address',
        'work_category',
        'experience',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'documents',
    ];
    
    protected $casts = [
        'documents' => 'array',
    ];
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';
    
    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function evaluations()
    {
        return $this->hasMany(VendorEvaluation::class);
    }
    
    public function scores()
    {
        return $this->hasMany(VendorScore::class);
    }
    
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }
    
    public function goodsReceipts()
    {
        return $this->hasManyThrough(
            GoodsReceipt::class,
            PurchaseOrder::class,
            'vendor_id', // Foreign key on purchase_orders table
            'purchase_order_id', // Foreign key on goods_receipts table
            'id', // Local key on vendors table
            'id' // Local key on purchase_orders table
        );
    }
    
    // Accessors
    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            self::STATUS_SUSPENDED => 'ระงับ',
        ][$this->status] ?? 'ไม่ระบุ';
    }
    
    public function getStatusBadgeClassAttribute()
    {
        return [
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_APPROVED => 'bg-success',
            self::STATUS_REJECTED => 'bg-danger',
            self::STATUS_SUSPENDED => 'bg-secondary',
        ][$this->status] ?? 'bg-light';
    }
    
    public function getStatusIconAttribute()
    {
        return [
            self::STATUS_PENDING => 'fas fa-clock',
            self::STATUS_APPROVED => 'fas fa-check-circle',
            self::STATUS_REJECTED => 'fas fa-times-circle',
            self::STATUS_SUSPENDED => 'fas fa-pause-circle',
        ][$this->status] ?? 'fas fa-question-circle';
    }
    
    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    // Methods
    public function approve()
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }
    
    public function reject()
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }
    
    public function suspend()
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }
    
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }
    
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }
    
    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
}
