<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือนกำหนดส่งมอบ</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .alert-box {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .countdown {
            font-size: 36px;
            font-weight: 700;
            color: #e17055;
            margin: 10px 0;
        }
        .document-details {
            background-color: #f8f9fa;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .document-details h3 {
            margin-top: 0;
            color: #e17055;
            font-size: 18px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            background-color: #ffc107;
            color: #212529;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .urgent-badge {
            background-color: #dc3545;
            color: white;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .company-logo {
            color: #007bff;
            font-weight: bold;
            font-size: 18px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 20px 15px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-value {
                text-align: left;
                margin-top: 5px;
            }
            .countdown {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="icon">⏰</div>
            <h1>แจ้งเตือนกำหนดส่งมอบ</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                สวัสดีคุณ {{ $user->name }},
            </div>
            
            <div class="alert-box">
                <h2 style="margin: 0; color: #e17055;">🚨 กำลังจะครบกำหนด!</h2>
                <div class="countdown">{{ $daysUntilDelivery }}</div>
                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;">วัน</p>
                <p style="margin: 0; color: #6c757d;">
                    @if($recordType === 'purchase_order')
                        ใบ PO จะครบกำหนดส่งมอบ
                    @else
                        ใบ PR จะครบกำหนดการใช้งาน
                    @endif
                </p>
            </div>
            
            <p>
                @if($recordType === 'purchase_order')
                    เรามีความยินดีที่จะแจ้งเตือนให้ทราบว่า ใบ Purchase Order ของคุณกำลังจะครบกำหนดส่งมอบใน {{ $daysUntilDelivery }} วัน กรุณาเตรียมการและติดตามสถานะการส่งมอบ
                @else
                    เรามีความยินดีที่จะแจ้งเตือนให้ทราบว่า ใบ Purchase Requisition ของคุณกำลังจะครบกำหนดที่ต้องการใช้งานใน {{ $daysUntilDelivery }} วัน กรุณาเตรียมการและติดตามสถานะ
                @endif
            </p>
            
            <div class="document-details">
                <h3>📋 รายละเอียด{{ $recordType === 'purchase_order' ? 'ใบ PO' : 'ใบ PR' }}</h3>
                
                <div class="detail-row">
                    <span class="detail-label">เลขที่เอกสาร:</span>
                    <span class="detail-value"><strong>{{ $documentNumber }}</strong></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">หัวข้อ:</span>
                    <span class="detail-value">{{ $documentTitle }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">กำหนด{{ $recordType === 'purchase_order' ? 'ส่งมอบ' : 'การใช้งาน' }}:</span>
                    <span class="detail-value">
                        <strong style="color: #e17055;">{{ $deliveryDate ? $deliveryDate->format('d/m/Y') : 'ไม่ได้ระบุ' }}</strong>
                    </span>
                </div>
                
                @if($recordType === 'purchase_order')
                    @if($record->vendor_name)
                    <div class="detail-row">
                        <span class="detail-label">ผู้ขาย:</span>
                        <span class="detail-value">{{ $record->vendor_name }}</span>
                    </div>
                    @endif
                    
                    @if($record->total_amount)
                    <div class="detail-row">
                        <span class="detail-label">มูลค่า:</span>
                        <span class="detail-value">{{ number_format($record->total_amount, 2) }} {{ $record->currency ?? 'THB' }}</span>
                    </div>
                    @endif
                @else
                    @if($record->supplier_name)
                    <div class="detail-row">
                        <span class="detail-label">ผู้ขาย:</span>
                        <span class="detail-value">{{ $record->supplier_name }}</span>
                    </div>
                    @endif
                    
                    @if($record->total_amount)
                    <div class="detail-row">
                        <span class="detail-label">มูลค่า:</span>
                        <span class="detail-value">{{ number_format($record->total_amount, 2) }} {{ $record->currency ?? 'THB' }}</span>
                    </div>
                    @endif
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">สถานะแจ้งเตือน:</span>
                    <span class="detail-value">
                        <span class="status-badge {{ $daysUntilDelivery <= 7 ? 'urgent-badge' : '' }}">
                            {{ $daysUntilDelivery <= 7 ? 'เร่งด่วน' : 'แจ้งเตือน' }}
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่สร้างเอกสาร:</span>
                    <span class="detail-value">{{ $record->created_at ? $record->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
            </div>
            
            @if($record->notes)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>📝 หมายเหตุ:</strong><br>
                {{ $record->notes }}
            </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                @if($recordType === 'purchase_order')
                    <a href="{{ config('app.url') }}/admin/purchase-orders/{{ $record->id }}" class="action-button">
                        🔍 ดูรายละเอียดใบ PO
                    </a>
                @else
                    <a href="{{ config('app.url') }}/admin/purchase-requisitions/{{ $record->id }}" class="action-button">
                        🔍 ดูรายละเอียดใบ PR
                    </a>
                @endif
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <h4 style="color: #e17055; margin-bottom: 15px;">⚡ ขั้นตอนที่ต้องดำเนินการ</h4>
                @if($recordType === 'purchase_order')
                    <ul style="color: #6c757d; padding-left: 20px;">
                        <li><strong>ติดตามสถานะกับผู้ขาย:</strong> ติดต่อผู้ขายเพื่อยืนยันสถานะการจัดส่ง</li>
                        <li><strong>เตรียมสถานที่รับของ:</strong> จัดเตรียมสถานที่และเจ้าหน้าที่รับมอบ</li>
                        <li><strong>แจ้งคณะกรรมการตรวจรับ:</strong> ประสานคณะกรรมการตรวจรับให้พร้อมในวันส่งมอบ</li>
                        <li><strong>เตรียมเอกสารประกอบ:</strong> ตรวจสอบเอกสารที่จำเป็นสำหรับการตรวจรับ</li>
                        @if($daysUntilDelivery <= 7)
                            <li><strong style="color: #dc3545;">เร่งด่วน:</strong> เหลือเวลาน้อยกว่า 7 วัน กรุณาติดตามอย่างใกล้ชิด</li>
                        @endif
                    </ul>
                @else
                    <ul style="color: #6c757d; padding-left: 20px;">
                        <li><strong>ตรวจสอบความพร้อม:</strong> ตรวจสอบความพร้อมของแผนงานที่ต้องการใช้</li>
                        <li><strong>ประสานหน่วยงานที่เกี่ยวข้อง:</strong> แจ้งหน่วยงานที่เกี่ยวข้องเพื่อเตรียมความพร้อม</li>
                        <li><strong>จัดเตรียมทรัพยากร:</strong> เตรียมทรัพยากรที่จำเป็นให้พร้อมใช้งาน</li>
                        <li><strong>ติดตามการอนุมัติ:</strong> ตรวจสอบสถานะการอนุมัติและดำเนินการตามขั้นตอน</li>
                        @if($daysUntilDelivery <= 7)
                            <li><strong style="color: #dc3545;">เร่งด่วน:</strong> เหลือเวลาน้อยกว่า 7 วัน กรุณาเร่งดำเนินการ</li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
        
        <div class="footer">
            <div class="company-logo">🏢 INNOBIC</div>
            <p>ระบบแจ้งเตือนอัตโนมัติ<br>
            อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            <p style="font-size: 12px; color: #adb5bd;">
                หากคุณมีคำถามใดๆ กรุณาติดต่อทีมจัดซื้อ หรือ IT Support<br>
                การแจ้งเตือนนี้จะส่งให้อีกครั้งในวันที่ {{ $deliveryDate ? $deliveryDate->subDays(7)->format('d/m/Y') : 'N/A' }} และ {{ $deliveryDate ? $deliveryDate->subDays(1)->format('d/m/Y') : 'N/A' }}
            </p>
        </div>
    </div>
</body>
</html>