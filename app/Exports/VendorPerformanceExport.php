<?php

namespace App\Exports;

use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class VendorPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $companyId;
    protected $vendorId;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($companyId = null, $vendorId = null, $dateFrom = null, $dateTo = null)
    {
        $this->companyId = $companyId;
        $this->vendorId = $vendorId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = Vendor::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->vendorId, fn($q) => $q->where('id', $this->vendorId));

        $vendors = $query->with(['purchaseOrders', 'goodsReceipts'])->get();

        $performanceData = new Collection();

        foreach ($vendors as $vendor) {
            // Get POs and GRs within date range
            $purchaseOrders = $vendor->purchaseOrders()
                ->when($this->dateFrom, fn($q) => $q->where('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->where('created_at', '<=', $this->dateTo))
                ->get();

            $goodsReceipts = GoodsReceipt::whereIn('purchase_order_id', $purchaseOrders->pluck('id'))
                ->when($this->dateFrom, fn($q) => $q->where('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->where('created_at', '<=', $this->dateTo))
                ->get();

            // Calculate metrics
            $totalPOs = $purchaseOrders->count();
            $totalPOValue = $purchaseOrders->sum('total_amount');
            $completedPOs = $purchaseOrders->whereIn('status', ['completed', 'closed', 'delivered'])->count();
            $totalGRs = $goodsReceipts->count();
            // Consider on-time if inspection_status is approved or completed
            $onTimeDeliveries = $goodsReceipts->whereIn('inspection_status', ['approved', 'completed', 'passed'])->count();
            // Consider quality passed if is_quality_checked is true and inspection_status is approved
            $qualityPassed = $goodsReceipts->where('is_quality_checked', true)
                ->whereIn('inspection_status', ['approved', 'completed', 'passed'])->count();
            
            // Calculate percentages
            $completionRate = $totalPOs > 0 ? round(($completedPOs / $totalPOs) * 100, 2) : 0;
            $onTimeRate = $totalGRs > 0 ? round(($onTimeDeliveries / $totalGRs) * 100, 2) : 0;
            $qualityRate = $totalGRs > 0 ? round(($qualityPassed / $totalGRs) * 100, 2) : 0;
            $overallScore = round(($completionRate + $onTimeRate + $qualityRate) / 3, 2);

            $performanceData->push([
                'vendor' => $vendor,
                'total_pos' => $totalPOs,
                'total_po_value' => $totalPOValue,
                'completed_pos' => $completedPOs,
                'completion_rate' => $completionRate,
                'total_deliveries' => $totalGRs,
                'on_time_deliveries' => $onTimeDeliveries,
                'on_time_rate' => $onTimeRate,
                'quality_passed' => $qualityPassed,
                'quality_rate' => $qualityRate,
                'overall_score' => $overallScore,
                'rating' => $this->getRating($overallScore),
            ]);
        }

        return $performanceData->sortByDesc('overall_score');
    }

    public function map($row): array
    {
        return [
            $row['vendor']->id,
            $row['vendor']->company_name,
            $row['vendor']->tax_id,
            $row['vendor']->work_category,
            $row['total_pos'],
            number_format($row['total_po_value'], 2),
            $row['completed_pos'],
            $row['completion_rate'] . '%',
            $row['total_deliveries'],
            $row['on_time_deliveries'],
            $row['on_time_rate'] . '%',
            $row['quality_passed'],
            $row['quality_rate'] . '%',
            $row['overall_score'] . '%',
            $row['rating'],
        ];
    }

    public function headings(): array
    {
        return [
            'รหัสผู้ขาย',
            'ชื่อบริษัท',
            'เลขประจำตัวผู้เสียภาษี',
            'ประเภทงาน',
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

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Style header row
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A5568'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Apply borders to all data cells
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        // Color code rating column based on performance
        for ($row = 2; $row <= $lastRow; $row++) {
            $rating = $sheet->getCell('O' . $row)->getValue();
            $color = match($rating) {
                'ดีเยี่ยม' => '10B981', // green
                'ดีมาก' => '3B82F6',   // blue
                'ดี' => 'F59E0B',      // yellow
                'พอใช้' => 'EF4444',   // red
                default => 'FFFFFF',
            };
            
            $sheet->getStyle('O' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ]);
        }

        // Center align percentage columns
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('K:K')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('M:M')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('N:N')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('O:O')->getAlignment()->setHorizontal('center');

        return [];
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