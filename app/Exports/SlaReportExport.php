<?php

namespace App\Exports;

use App\Models\SlaTracking;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export รายงาน SLA (stage: รับเรื่อง → PO Approved)
 * คอลัมน์ตามไฟล์ Excel "ตัวอย่างการคิด SLA" ที่ผู้ใช้ใช้กันอยู่
 */
class SlaReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(protected ?int $companyId = null) {}

    public function array(): array
    {
        $trackings = SlaTracking::query()
            ->where('stage', 'received_to_po_approval')
            ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
            ->with(['purchaseRequisition', 'purchaseOrder'])
            ->orderBy('start_date')
            ->get();

        return $trackings->map(function (SlaTracking $t) {
            $pr = $t->purchaseRequisition;
            $po = $t->purchaseOrder;

            return [
                $pr?->pr_number,
                $pr?->pr_approved_at?->format('d/m/Y'),
                $t->start_date?->format('d/m/Y'),
                $this->methodLabel($t->procurement_method),
                $t->sla_standard_days,
                $t->actual_working_days,
                $t->getPercentDiff() !== null ? $t->getPercentDiff().'%' : '',
                $t->saving_percentage !== null ? $t->saving_percentage.'%' : '',
                $pr?->title,
                $t->budget_amount !== null ? number_format((float) $t->budget_amount, 2) : '',
                $t->final_amount !== null ? number_format((float) $t->final_amount, 2) : '',
                $t->saving_amount !== null ? number_format((float) $t->saving_amount, 2) : '',
                $po?->currency ?? $pr?->currency ?? 'THB',
                $po?->po_number,
                $t->end_date?->format('d/m/Y'),
                $po?->total_amount !== null ? number_format((float) $po->total_amount, 2) : '',
                $t->sla_grade,
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'เลขที่ PR',
            'วันที่อนุมัติ (PR)',
            'วันที่รับเรื่อง',
            'วิธีในการจัดซื้อจัดจ้าง',
            'SLA',
            'Datedif',
            '%Dif',
            '%Saving',
            'ชื่องาน',
            'วงเงินก่อน VAT',
            'ราคาที่ต่อได้',
            'ส่วนต่าง',
            'สกุลเงิน',
            'เลขที่ PO',
            'วันที่อนุมัติ PO',
            'จำนวนเงิน (PO)',
            'เกรด',
        ];
    }

    public function title(): string
    {
        return 'SLA';
    }

    protected function methodLabel(?string $method): string
    {
        return match ($method) {
            'agreement_price' => 'วิธีตกลงราคา',
            'invitation_bid' => 'วิธีประมูล (โดยการเชิญ)',
            'open_bid' => 'วิธีประมูล (โดยการประกาศเชิญชวนทั่วไป)',
            'special_1' => 'วิธีพิเศษ ข้อ 1',
            'special_2' => 'วิธีพิเศษ ข้อ 2',
            'selection' => 'วิธีคัดเลือก',
            default => (string) $method,
        };
    }
}
