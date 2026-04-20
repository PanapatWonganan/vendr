<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'freeserif', serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 5px 0;
        }
        .header h2 {
            font-size: 16px;
            margin: 5px 0;
            color: #555;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .meta-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .meta-table .label {
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 8px 12px;
            margin: 20px 0 10px 0;
            border-left: 4px solid #333;
        }
        .content {
            padding: 0 12px;
            margin-bottom: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .criteria-table th, .criteria-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .criteria-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin-top: 60px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 0 auto;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-approved { background: #d4edda; color: #155724; }
        .status-draft { background: #e2e3e5; color: #383d41; }
        .status-submitted { background: #cce5ff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="company-name">{{ $company['name'] }}</div>
    <h1>ขอบเขตของงาน (Terms of Reference - TOR)</h1>
    <h2>{{ $tor->tor_number }}
        @if($tor->isRevision())
            (Rev.{{ $tor->revision_number }})
        @endif
    </h2>
</div>

{{-- Basic Info --}}
<table class="meta-table">
    <tr>
        <td class="label">ชื่อ TOR:</td>
        <td colspan="3">{{ $tor->title }}</td>
    </tr>
    <tr>
        <td class="label">ประเภท TOR:</td>
        <td>{{ $torTypeLabel }}</td>
        <td class="label">สถานะ:</td>
        <td>
            <span class="status-badge status-{{ $tor->status }}">{{ $statusLabel }}</span>
        </td>
    </tr>
    <tr>
        <td class="label">ประเภทงาน:</td>
        <td>{{ $workTypeLabel }}</td>
        <td class="label">วิธีจัดซื้อ:</td>
        <td>{{ $procurementMethodLabel }}</td>
    </tr>
    <tr>
        <td class="label">แผนก:</td>
        <td>{{ $tor->department->name ?? '-' }}</td>
        <td class="label">ลำดับสำคัญ:</td>
        <td>{{ $priorityLabel }}</td>
    </tr>
    @if($tor->project_name || $tor->project_code)
    <tr>
        <td class="label">โครงการ:</td>
        <td>{{ $tor->project_name ?? '-' }}</td>
        <td class="label">รหัสโครงการ:</td>
        <td>{{ $tor->project_code ?? '-' }}</td>
    </tr>
    @endif
    <tr>
        <td class="label">งบประมาณ:</td>
        <td>{{ number_format($tor->budget_estimate ?? 0, 2) }} {{ $tor->currency }}</td>
        <td class="label">รหัสงบประมาณ:</td>
        <td>{{ $tor->budget_code ?? '-' }}</td>
    </tr>
    @if($tor->start_date || $tor->end_date)
    <tr>
        <td class="label">ระยะเวลา:</td>
        <td colspan="3">
            {{ $tor->start_date ? $tor->start_date->format('d/m/Y') : '-' }}
            ถึง
            {{ $tor->end_date ? $tor->end_date->format('d/m/Y') : '-' }}
            @if($tor->duration_days)
                ({{ $tor->duration_days }} วัน)
            @endif
        </td>
    </tr>
    @endif
</table>

{{-- Background --}}
@if($tor->background)
<div class="section-title">1. ความเป็นมา / หลักการและเหตุผล</div>
<div class="content">{!! $tor->background !!}</div>
@endif

{{-- Objectives --}}
@if($tor->objectives)
<div class="section-title">2. วัตถุประสงค์</div>
<div class="content">{!! $tor->objectives !!}</div>
@endif

{{-- Scope of Work --}}
<div class="section-title">3. ขอบเขตของงาน</div>
<div class="content">{!! $tor->scope_of_work !!}</div>

{{-- Deliverables --}}
@if($tor->deliverables)
<div class="section-title">4. ผลงานที่ต้องส่งมอบ</div>
<div class="content">{!! $tor->deliverables !!}</div>
@endif

{{-- Specifications --}}
@if($tor->specifications)
<div class="section-title">5. ข้อกำหนดทางเทคนิค</div>
<div class="content">{!! $tor->specifications !!}</div>
@endif

{{-- Items Table --}}
@if($tor->items->count() > 0)
<div class="section-title">6. รายการ</div>
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 5%">ลำดับ</th>
            <th style="width: 35%">รายละเอียด</th>
            <th style="width: 10%">จำนวน</th>
            <th style="width: 10%">หน่วย</th>
            <th style="width: 15%">ราคา/หน่วย</th>
            <th style="width: 15%">ราคารวม</th>
            <th style="width: 10%">ต้องการภายใน</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; @endphp
        @foreach($tor->items as $item)
        <tr>
            <td class="text-center">{{ $item->item_number }}</td>
            <td>{{ $item->description }}</td>
            <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
            <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
            <td class="text-right">{{ $item->estimated_unit_price ? number_format($item->estimated_unit_price, 2) : '-' }}</td>
            <td class="text-right">{{ $item->estimated_total_price ? number_format($item->estimated_total_price, 2) : '-' }}</td>
            <td class="text-center">{{ $item->required_date ? $item->required_date->format('d/m/Y') : '-' }}</td>
        </tr>
        @php $grandTotal += $item->estimated_total_price ?? 0; @endphp
        @endforeach
        <tr>
            <td colspan="5" style="text-align: right; font-weight: bold;">รวมทั้งสิ้น</td>
            <td class="text-right" style="font-weight: bold;">{{ number_format($grandTotal, 2) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
@endif

{{-- Qualification Requirements --}}
@if($tor->qualification_requirements)
<div class="section-title">7. คุณสมบัติผู้เสนอราคา</div>
<div class="content">{!! $tor->qualification_requirements !!}</div>
@endif

{{-- Evaluation Criteria --}}
@if($tor->evaluation_criteria && count($tor->evaluation_criteria) > 0)
<div class="section-title">8. เกณฑ์การประเมิน</div>
<table class="criteria-table">
    <thead>
        <tr>
            <th style="width: 5%">ลำดับ</th>
            <th style="width: 30%">เกณฑ์</th>
            <th style="width: 15%">น้ำหนัก (%)</th>
            <th style="width: 50%">คำอธิบาย</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tor->evaluation_criteria as $index => $criteria)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $criteria['name'] ?? '' }}</td>
            <td class="text-center">{{ $criteria['weight'] ?? 0 }}%</td>
            <td>{{ $criteria['description'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Payment Terms --}}
@if($tor->payment_terms)
<div class="section-title">9. เงื่อนไขการจ่ายเงิน</div>
<div class="content">{!! $tor->payment_terms !!}</div>
@endif

{{-- Warranty --}}
@if($tor->warranty_requirements)
<div class="section-title">10. การรับประกัน</div>
<div class="content">{!! $tor->warranty_requirements !!}</div>
@endif

{{-- Penalty --}}
@if($tor->penalty_clause)
<div class="section-title">11. เงื่อนไขเบี้ยปรับ</div>
<div class="content">{!! $tor->penalty_clause !!}</div>
@endif

{{-- Committee --}}
@if($tor->procurementCommittee || $tor->inspectionCommittee)
<div class="section-title">12. คณะกรรมการ</div>
<table class="meta-table">
    @if($tor->procurementCommittee)
    <tr>
        <td class="label">คณะกรรมการจัดซื้อ:</td>
        <td>{{ $tor->procurementCommittee->name }}</td>
    </tr>
    @endif
    @if($tor->inspectionCommittee)
    <tr>
        <td class="label">คณะกรรมการตรวจรับ:</td>
        <td>{{ $tor->inspectionCommittee->name }}</td>
    </tr>
    @endif
</table>
@endif

{{-- Signatures --}}
<div class="signature-section">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 60px;">
                <div class="signature-line">
                    ({{ $tor->creator->name ?? '...........................' }})<br>
                    ผู้จัดทำ<br>
                    วันที่: {{ $tor->created_at?->format('d/m/Y') ?? '.../.../...' }}
                </div>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 60px;">
                <div class="signature-line">
                    ({{ $tor->approver->name ?? '...........................' }})<br>
                    ผู้อนุมัติ<br>
                    วันที่: {{ $tor->approved_at?->format('d/m/Y') ?? '.../.../...' }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Footer --}}
<div class="footer">
    พิมพ์เมื่อ: {{ $printDate }} | {{ $company['name'] }} | เอกสารนี้จัดทำจากระบบ VENDR
</div>

</body>
</html>
