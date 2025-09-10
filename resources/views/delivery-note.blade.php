<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>หนังสือส่งมอบงาน - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'THSarabunNew', 'Sarabun', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #000;
            background: white;
        }
        
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        
        .company-logo {
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .company-address {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0;
            text-align: center;
            text-decoration: underline;
        }
        
        .document-info {
            margin-bottom: 30px;
        }
        
        .info-row {
            margin-bottom: 10px;
            display: flex;
        }
        
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
            padding-bottom: 2px;
        }
        
        .content-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 10px;
        }
        
        .date-line {
            margin-bottom: 10px;
        }
        
        .notes-section {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        
        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .notes-content {
            min-height: 60px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="company-name">{{ $company['name'] ?? 'บริษัท อินโนบิค นูทริชั่น จำกัด' }}</div>
            <div class="company-address">{{ $company['address'] ?? 'เลขที่ 425/1 อาคาร เอนโก้เทอร์มินอล อาคาร บี ชั้น 7 ถนนกำแพงเพชร 6 แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210' }}</div>
            <div class="company-address">โทรศัพท์: {{ $company['phone'] ?? '02-111-6289' }} | อีเมล: {{ $company['email'] ?? 'info@innobic.com' }}</div>
        </div>

        <!-- Document Title -->
        <div class="document-title">หนังสือส่งมอบงาน</div>

        <!-- Document Information -->
        <div class="document-info">
            <div class="info-row">
                <div class="info-label">เลขที่หนังสือ:</div>
                <div class="info-value">DN-{{ $purchaseOrder->po_number }}-{{ now()->format('Ymd') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">วันที่:</div>
                <div class="info-value">{{ now()->locale('th')->translatedFormat('j F พ.ศ. Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">อ้างอิงใบสั่งซื้อ:</div>
                <div class="info-value">{{ $purchaseOrder->po_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">ถึง:</div>
                <div class="info-value">{{ $vendorName ?? $purchaseOrder->vendor_name ?? 'ผู้รับเหมา/ผู้ขาย' }}</div>
            </div>
            @if($vendorAddress ?? $purchaseOrder->delivery_address)
            <div class="info-row">
                <div class="info-label">ที่อยู่:</div>
                <div class="info-value">{{ $vendorAddress ?? $purchaseOrder->delivery_address }}</div>
            </div>
            @endif
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <p>เรียน {{ $vendorName ?? $purchaseOrder->vendor_name ?? 'ผู้รับเหมา/ผู้ขาย' }}</p>
            <p style="margin: 20px 0;">
                ตามที่ท่านได้รับใบสั่งซื้อ/สั่งจ้างเลขที่ <strong>{{ $purchaseOrder->po_number }}</strong> 
                ลงวันที่ <strong>{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->locale('th')->translatedFormat('j F พ.ศ. Y') : 'N/A' }}</strong> 
                เรื่อง <strong>{{ $purchaseOrder->po_title ?? $purchaseOrder->description }}</strong> นั้น
            </p>
            <p style="margin: 20px 0;">
                บัดนี้ท่านได้ส่งมอบงาน/สินค้าตามรายการดังต่อไปนี้แล้ว:
            </p>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th>รายการ</th>
                    <th style="width: 100px;">จำนวน</th>
                    <th style="width: 80px;">หน่วย</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                @if($purchaseOrder->items && $purchaseOrder->items->count() > 0)
                    @foreach($purchaseOrder->items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ $item->description ?? $item->item_name }}</td>
                        <td style="text-align: center;">{{ number_format($item->quantity ?? 1) }}</td>
                        <td style="text-align: center;">{{ $item->unit ?? 'ชุด' }}</td>
                        <td>{{ $item->notes ?? '' }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="text-align: center;">1</td>
                        <td>{{ $purchaseOrder->po_title ?? $purchaseOrder->description ?? 'งานตามใบสั่งซื้อ/สั่งจ้าง' }}</td>
                        <td style="text-align: center;">1</td>
                        <td style="text-align: center;">งาน</td>
                        <td>ตามรายละเอียดในใบสั่งซื้อ/สั่งจ้าง</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Delivery Information -->
        <div class="content-section">
            <div class="info-row">
                <div class="info-label">สถานที่ส่งมอบ:</div>
                <div class="info-value">{{ $purchaseOrder->delivery_location ?? $purchaseOrder->delivery_address ?? 'ตามที่กำหนดในใบสั่งซื้อ' }}</div>
            </div>
            @if($purchaseOrder->expected_delivery_date)
            <div class="info-row">
                <div class="info-label">กำหนดส่งมอบ:</div>
                <div class="info-value">{{ $purchaseOrder->expected_delivery_date->locale('th')->translatedFormat('j F พ.ศ. Y') }}</div>
            </div>
            @endif
            @if($purchaseOrder->contact_person)
            <div class="info-row">
                <div class="info-label">ผู้ติดต่อ:</div>
                <div class="info-value">{{ $purchaseOrder->contact_person }} ({{ $purchaseOrder->contact_phone ?? '' }})</div>
            </div>
            @endif
        </div>

        <!-- Notes Section -->
        <div class="notes-section">
            <div class="notes-title">หมายเหตุ:</div>
            <div class="notes-content">
                <p>• กรุณาตรวจสอบงาน/สินค้าที่ส่งมอบให้ถูกต้องครบถ้วนตามรายการ</p>
                <p>• หากพบข้อผิดพลาดหรือไม่ครบถ้วน กรุณาแจ้งกลับภายใน 7 วัน</p>
                <p>• เอกสารฉบับนี้เป็นหลักฐานการส่งมอบงาน/สินค้า</p>
                @if($purchaseOrder->notes)
                <p>• {{ $purchaseOrder->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">ผู้ส่งมอบ</div>
                <div class="date-line">วันที่: ___________________</div>
                <div class="signature-line">
                    ({{ $vendorName ?? $purchaseOrder->vendor_name ?? '____________________' }})
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-title">ผู้รับมอบ</div>
                <div class="date-line">วันที่: ___________________</div>
                <div class="signature-line">
                    ({{ $purchaseOrder->inspectionCommittee->name ?? $purchaseOrder->contact_person ?? '____________________' }})
                </div>
            </div>
        </div>
    </div>
</body>
</html>