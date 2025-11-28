<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบสั่งจ้าง - {{ $purchaseOrder->po_number }}</title>
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
        }
        
        .container {
            width: 100%;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .header .company-info {
            font-size: 14px;
            color: #666;
        }
        
        .document-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
        }
        
        .document-info {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding-right: 10px;
        }
        
        .info-value {
            display: table-cell;
            width: 70%;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 5px;
            background-color: #f8f8f8;
            border-left: 4px solid #007bff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .total-label {
            display: table-cell;
            width: 70%;
            text-align: right;
            padding-right: 20px;
            font-weight: bold;
        }
        
        .total-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin: 0 2%;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 50px 20px 10px 20px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $company['name'] }}</h1>
            <div class="company-info">
                {{ $company['address'] }}<br>
                โทร: {{ $company['phone'] }} | อีเมล: {{ $company['email'] }}<br>
                เลขประจำตัวผู้เสียภาษี: {{ $company['tax_id'] }}
            </div>
        </div>
        
        <!-- Document Title -->
        <div class="document-title">
            ใบสั่งจ้าง (HIRE PURCHASE ORDER)
        </div>
        
        <!-- Document Info -->
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">เลขที่ใบสั่งจ้าง:</span>
                <span class="info-value">
                    <strong>{{ $purchaseOrder->po_number }}</strong>
                    @if($purchaseOrder->sap_po_number)
                        (SAP: {{ $purchaseOrder->sap_po_number }})
                    @endif
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">วันที่สั่งจ้าง:</span>
                <span class="info-value">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d/m/Y') : '-' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">ประเภทงาน:</span>
                <span class="info-value">
                    <span class="badge badge-success">{{ $workTypeLabel }}</span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">วิธีการจัดหา:</span>
                <span class="info-value">
                    <span class="badge badge-warning">{{ $procurementMethodLabel }}</span>
                </span>
            </div>
            
            @if($purchaseOrder->purchaseRequisition)
            <div class="info-row">
                <span class="info-label">อ้างอิง PR:</span>
                <span class="info-value">{{ $purchaseOrder->purchaseRequisition->pr_number }}</span>
            </div>
            @endif
        </div>
        
        <!-- Vendor Information -->
        <div class="section-title">ข้อมูลผู้รับจ้าง</div>
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">ชื่อผู้รับจ้าง:</span>
                <span class="info-value">{{ $purchaseOrder->vendor_name ?? '-' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">ผู้ติดต่อ:</span>
                <span class="info-value">{{ $purchaseOrder->contact_name ?? '-' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">อีเมล:</span>
                <span class="info-value">{{ $purchaseOrder->contact_email ?? '-' }}</span>
            </div>
        </div>
        
        <!-- Work Details -->
        <div class="section-title">รายละเอียดงานจ้าง</div>
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">ชื่องาน:</span>
                <span class="info-value"><strong>{{ $purchaseOrder->po_title }}</strong></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">ระยะเวลาดำเนินการ:</span>
                <span class="info-value">{{ $purchaseOrder->operation_duration ?? '-' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">กำหนดส่งมอบ:</span>
                <span class="info-value">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d/m/Y') : '-' }}</span>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="section-title">รายการงานจ้าง</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 45%;">รายละเอียด</th>
                    <th style="width: 15%;" class="text-center">จำนวน</th>
                    <th style="width: 15%;" class="text-right">ราคาต่อหน่วย</th>
                    <th style="width: 20%;" class="text-right">รวม</th>
                </tr>
            </thead>
            <tbody>
                @if($purchaseOrder->items && count($purchaseOrder->items) > 0)
                    @foreach($purchaseOrder->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            {{ $item->description }}<br>
                            <small style="color: #666;">{{ $item->specifications ?? '' }}</small>
                        </td>
                        <td class="text-center">{{ $item->quantity }} {{ $item->unit ?? 'หน่วย' }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center">- ไม่มีรายการ -</td>
                    </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Total Section -->
        <div class="total-section">
            @if($purchaseOrder->stamp_duty && $purchaseOrder->stamp_duty > 0)
            <div class="total-row">
                <span class="total-label">อากรแสตมป์:</span>
                <span class="total-value">{{ number_format($purchaseOrder->stamp_duty, 2) }} บาท</span>
            </div>
            @endif
            
            <div class="total-row" style="font-size: 18px; color: #007bff;">
                <span class="total-label">มูลค่ารวมทั้งสิ้น:</span>
                <span class="total-value">{{ number_format($purchaseOrder->total_amount, 2) }} {{ $purchaseOrder->currency ?? 'บาท' }}</span>
            </div>
        </div>
        
        <!-- Payment Terms -->
        <div class="section-title">เงื่อนไขการชำระเงิน</div>
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">เงื่อนไขการชำระ:</span>
                <span class="info-value">{{ $purchaseOrder->payment_terms ?? 'ตามข้อตกลง' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">กำหนดการชำระ:</span>
                <span class="info-value">{{ $purchaseOrder->payment_schedule ?? '-' }}</span>
            </div>
        </div>
        
        <!-- Notes -->
        @if($purchaseOrder->notes)
        <div class="section-title">หมายเหตุ</div>
        <div class="document-info">
            <p>{{ $purchaseOrder->notes }}</p>
        </div>
        @endif
        
        <!-- Inspection Committee -->
        @if($purchaseOrder->inspectionCommittee)
        <div class="section-title">คณะกรรมการตรวจรับ</div>
        <div class="document-info">
            <div class="info-row">
                <span class="info-label">ประธานกรรมการ:</span>
                <span class="info-value">{{ $purchaseOrder->inspectionCommittee->name }}</span>
            </div>
        </div>
        @endif
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>ผู้สั่งจ้าง</div>
                <div style="font-size: 14px;">
                    ({{ $purchaseOrder->creator ? $purchaseOrder->creator->name : '.................................' }})<br>
                    วันที่ {{ $purchaseOrder->created_at ? $purchaseOrder->created_at->format('d/m/Y') : '...../...../.....' }}
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>ผู้อนุมัติ</div>
                <div style="font-size: 14px;">
                    ({{ $purchaseOrder->approver ? $purchaseOrder->approver->name : '.................................' }})<br>
                    วันที่ {{ $approvalDate }}
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>พิมพ์เมื่อ: {{ $printDate }} | เอกสารนี้ออกโดยระบบอัตโนมัติ</p>
            <p style="margin-top: 5px;">Innobic Purchase Management System</p>
        </div>
    </div>
</body>
</html>