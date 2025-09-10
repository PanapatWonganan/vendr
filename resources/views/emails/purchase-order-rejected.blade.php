<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบ PO ถูกปฏิเสธ</title>
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
            background: linear-gradient(135deg, #dc3545, #c82333);
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
        .po-details {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .po-details h3 {
            margin-top: 0;
            color: #dc3545;
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
            background-color: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .rejector-info {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .rejection-reason {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
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
        .edit-button {
            background: linear-gradient(135deg, #28a745, #1e7e34);
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
        .next-steps {
            background-color: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
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
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="icon">❌</div>
            <h1>ใบ PO ถูกปฏิเสธ</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                สวัสดีคุณ {{ $recipient->name }},
            </div>
            
            <p>เราขออภัยที่ต้องแจ้งให้ทราบว่า ใบ Purchase Order ของคุณได้ถูกปฏิเสธ กรุณาตรวจสอบรายละเอียดและเหตุผลในการปฏิเสธด้านล่าง</p>
            
            <div class="po-details">
                <h3>📋 รายละเอียดใบ PO</h3>
                
                <div class="detail-row">
                    <span class="detail-label">เลขที่ PO:</span>
                    <span class="detail-value"><strong>{{ $purchaseOrder->po_number }}</strong></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">หัวข้อ:</span>
                    <span class="detail-value">{{ $purchaseOrder->po_title }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">ผู้ขาย:</span>
                    <span class="detail-value">{{ $purchaseOrder->vendor_name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">มูลค่า:</span>
                    <span class="detail-value">{{ number_format($purchaseOrder->total_amount, 2) }} {{ $purchaseOrder->currency }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">ความสำคัญ:</span>
                    <span class="detail-value">{{ $purchaseOrder->priority_text }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">สถานะ:</span>
                    <span class="detail-value"><span class="status-badge">ถูกปฏิเสธ</span></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่สร้าง:</span>
                    <span class="detail-value">{{ $purchaseOrder->created_at ? $purchaseOrder->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่ปฏิเสธ:</span>
                    <span class="detail-value">{{ $purchaseOrder->rejected_at ? $purchaseOrder->rejected_at->format('d/m/Y H:i') : 'เพิ่งปฏิเสธ' }}</span>
                </div>
            </div>
            
            <div class="rejector-info">
                <strong>👤 ผู้ปฏิเสธ:</strong> {{ $rejector->name }}<br>
                <strong>📧 อีเมล:</strong> {{ $rejector->email }}<br>
                <strong>⏰ เวลาที่ปฏิเสธ:</strong> {{ $purchaseOrder->rejected_at ? $purchaseOrder->rejected_at->format('d/m/Y เวลา H:i น.') : 'เพิ่งปฏิเสธ' }}
            </div>
            
            <div class="rejection-reason">
                <strong>🚫 เหตุผลในการปฏิเสธ:</strong><br>
                <div style="margin-top: 10px; padding: 10px; background-color: #ffffff; border-radius: 3px; border: 1px solid #e9ecef;">
                    {{ $rejectionReason }}
                </div>
            </div>
            
            @if($purchaseOrder->notes)
            <div style="background-color: #e2e3e5; border: 1px solid #c6c8ca; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>📝 หมายเหตุเดิม:</strong><br>
                {{ $purchaseOrder->notes }}
            </div>
            @endif
            
            <div class="next-steps">
                <h4 style="color: #0056b3; margin-top: 0; margin-bottom: 15px;">📋 ขั้นตอนต่อไป</h4>
                <ul style="color: #495057; padding-left: 20px; margin-bottom: 0;">
                    <li>ตรวจสอบและแก้ไขใบ PO ตามเหตุผลที่ถูกปฏิเสธ</li>
                    <li>อัปเดตข้อมูลที่จำเป็น เช่น ราคา, รายละเอียดสินค้า, ผู้ขาย</li>
                    <li>เพิ่มเติมเอกสารหรือข้อมูลที่จำเป็น</li>
                    <li>ส่งใบ PO เพื่อขออนุมัติใหม่อีกครั้ง</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="action-button">
                    🔍 ดูรายละเอียดใบ PO
                </a>
                
                @if($purchaseOrder->canEdit())
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="action-button edit-button">
                    ✏️ แก้ไขใบ PO
                </a>
                @endif
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <h4 style="color: #dc3545; margin-bottom: 15px;">💡 คำแนะนำ</h4>
                <div style="color: #6c757d;">
                    <p><strong>การตรวจสอบก่อนส่งใหม่:</strong></p>
                    <ul style="padding-left: 20px;">
                        <li>ตรวจสอบว่าข้อมูลผู้ขายถูกต้องและเป็นปัจจุบัน</li>
                        <li>ยืนยันราคาและเงื่อนไขการชำระเงิน</li>
                        <li>แนบเอกสารประกอบที่ครบถ้วน</li>
                        <li>ตรวจสอบงบประมาณและการอนุมัติที่จำเป็น</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="company-logo">🏢 INNOBIC</div>
            <p>ระบบจัดการ Purchase Order<br>
            อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            <p style="font-size: 12px; color: #adb5bd;">
                หากคุณมีคำถามใดๆ กรุณาติดต่อผู้อนุมัติโดยตรง หรือ IT Support
            </p>
        </div>
    </div>
</body>
</html> 