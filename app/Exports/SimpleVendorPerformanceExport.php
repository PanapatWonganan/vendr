<?php

namespace App\Exports;

use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SimpleVendorPerformanceExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId ?: 1;
    }

    public function array(): array
    {
        $vendors = Vendor::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->with(['purchaseOrders', 'goodsReceipts'])
            ->get();

        $data = [];

        foreach ($vendors as $vendor) {
            // Get all POs for this vendor
            $purchaseOrders = $vendor->purchaseOrders;
            $goodsReceipts = $vendor->goodsReceipts;

            // Calculate metrics
            $totalPOs = $purchaseOrders->count();
            $totalPOValue = $purchaseOrders->sum('total_amount');
            $completedPOs = $purchaseOrders->whereIn('status', ['completed', 'closed', 'delivered', 'approved'])->count();
            $totalGRs = $goodsReceipts->count();
            
            // Consider on-time if inspection_status is approved or completed or passed
            $onTimeDeliveries = $goodsReceipts->whereIn('inspection_status', ['approved', 'completed', 'passed'])->count();
            
            // Consider quality passed if inspection_status is approved/passed
            $qualityPassed = $goodsReceipts->whereIn('inspection_status', ['approved', 'completed', 'passed'])->count();
            
            // Calculate percentages
            $completionRate = $totalPOs > 0 ? round(($completedPOs / $totalPOs) * 100, 2) : 0;
            $onTimeRate = $totalGRs > 0 ? round(($onTimeDeliveries / $totalGRs) * 100, 2) : 0;
            $qualityRate = $totalGRs > 0 ? round(($qualityPassed / $totalGRs) * 100, 2) : 0;
            $overallScore = round(($completionRate + $onTimeRate + $qualityRate) / 3, 2);

            $data[] = [
                $vendor->id,
                $vendor->company_name,
                $vendor->tax_id,
                $vendor->work_category,
                $vendor->contact_name,
                $vendor->contact_phone,
                $vendor->contact_email,
                $totalPOs,
                number_format($totalPOValue, 2),
                $completedPOs,
                $completionRate . '%',
                $totalGRs,
                $onTimeDeliveries,
                $onTimeRate . '%',
                $qualityPassed,
                $qualityRate . '%',
                $overallScore . '%',
                $this->getRating($overallScore),
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'รหัสผู้ขาย',
            'ชื่อบริษัท',
            'เลขประจำตัวผู้เสียภาษี',
            'ประเภทงาน',
            'ผู้ติดต่อ',
            'เบอร์โทร',
            'อีเมล',
            'จำนวน PO',
            'มูลค่า PO รวม (บาท)',
            'PO ที่เสร็จสมบูรณ์',
            'อัตราความสำเร็จ',
            'จำนวนการส่งมอบ',
            'ส่งมอบตรงเวลา',
            'อัตราการส่งตรงเวลา',
            'ผ่าน QC',
            'อัตราการผ่าน QC',
            'คะแนนรวม',
            'เรตติ้ง',
        ];
    }

    public function title(): string
    {
        return 'Vendor Performance Report';
    }

    private function getRating($score)
    {
        if ($score >= 90) return 'ดีเยี่ยม';
        if ($score >= 75) return 'ดีมาก';
        if ($score >= 60) return 'ดี';
        return 'พอใช้';
    }
}