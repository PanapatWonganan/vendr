<?php

namespace App\Exports;

use App\Models\VendorEvaluation;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * ข้อ 18: ดาวน์โหลดข้อมูลการประเมินผลงานเป็นไฟล์ Excel
 */
class VendorEvaluationExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    protected ?int $companyId;

    /** @var array<int>|null */
    protected ?array $ids;

    public function __construct(?int $companyId = null, ?array $ids = null)
    {
        $this->companyId = $companyId;
        $this->ids = $ids;
    }

    public function array(): array
    {
        $evaluations = VendorEvaluation::query()
            ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
            ->when($this->ids, fn ($q) => $q->whereIn('id', $this->ids))
            ->with(['vendor', 'purchaseOrder', 'evaluator'])
            ->orderByDesc('created_at')
            ->get();

        $statusLabels = [
            'draft' => 'ร่าง',
            'submitted' => 'ส่งแล้ว',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ปฏิเสธ',
        ];

        $rows = [];
        foreach ($evaluations as $e) {
            $rows[] = [
                $e->id,
                $e->purchaseOrder->po_number ?? '-',
                $e->vendor->company_name ?? '-',
                $e->project_name ?? '-',
                $e->payment_term_number ? "งวดที่ {$e->payment_term_number}" : '-',
                $e->evaluation_period ?? '-',
                $e->evaluation_date ? $e->evaluation_date->format('d/m/Y') : '-',
                $e->average_score ? number_format($e->average_score, 2).'/4.00' : 'ยังไม่คำนวณ',
                $e->score_grade ?? '-',
                $e->score_grade_detail ?? '-',
                $statusLabels[$e->status] ?? $e->status,
                $e->evaluator->name ?? '-',
                $e->general_comments ?? '',
                $e->recommendations ?? '',
                $e->areas_for_improvement ?? '',
                $e->created_at ? $e->created_at->format('d/m/Y H:i') : '-',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'รหัส',
            'เลขที่ PO',
            'ผู้ขาย',
            'ชื่องาน',
            'งวดที่ชำระ',
            'รอบประเมิน',
            'วันที่ประเมิน',
            'คะแนนเฉลี่ย',
            'เกรด',
            'ผลการประเมิน',
            'สถานะ',
            'ผู้ประเมิน',
            'ความคิดเห็นทั่วไป',
            'ข้อเสนอแนะ',
            'จุดที่ควรพัฒนา',
            'วันที่สร้าง',
        ];
    }

    public function title(): string
    {
        return 'Vendor Evaluations';
    }
}
