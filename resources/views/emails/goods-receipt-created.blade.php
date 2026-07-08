<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบตรวจรับงาน/วัสดุใหม่</title>
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
            background: linear-gradient(135deg, #17a2b8, #0056b3);
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
        .gr-details {
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .gr-details h3 {
            margin-top: 0;
            color: #17a2b8;
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
            background-color: #17a2b8;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .creator-info {
            background-color: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
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
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="icon">📦</div>
            <h1>ใบตรวจรับงาน/วัสดุใหม่</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                สวัสดีคุณ {{ $inspectionCommittee ? $inspectionCommittee->name : 'เจ้าหน้าที่' }},
            </div>
            
            <p>เรามีความยินดีที่จะแจ้งให้ทราบว่า มีใบตรวจรับงาน/วัสดุใหม่ที่ต้องการให้คุณดำเนินการตรวจสอบ กรุณาดำเนินการตามรายละเอียดดังต่อไปนี้</p>
            
            <div class="gr-details">
                <h3>📋 รายละเอียดใบตรวจรับงาน/วัสดุ</h3>
                
                <div class="detail-row">
                    <span class="detail-label">เลขที่ GR:</span>
                    <span class="detail-value"><strong>{{ $goodsReceipt->gr_number ?: 'ยังไม่ได้สร้าง' }}</strong></span>
                </div>
                
                @if($goodsReceipt->purchaseOrder)
                <div class="detail-row">
                    <span class="detail-label">เลขที่ PO:</span>
                    <span class="detail-value">{{ $goodsReceipt->purchaseOrder->po_number }}</span>
                </div>
                @endif
                
                @if($goodsReceipt->vendor)
                <div class="detail-row">
                    <span class="detail-label">ผู้ขาย:</span>
                    <span class="detail-value">{{ $goodsReceipt->vendor->company_name }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">วันที่รับ:</span>
                    <span class="detail-value">{{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('d/m/Y') : 'ยังไม่ได้กำหนด' }}</span>
                </div>
                
                @if($goodsReceipt->delivery_milestone)
                <div class="detail-row">
                    <span class="detail-label">งวดที่:</span>
                    <span class="detail-value">งวดที่ {{ $goodsReceipt->delivery_milestone }}</span>
                </div>
                @endif
                
                @if($goodsReceipt->milestone_percentage)
                <div class="detail-row">
                    <span class="detail-label">เปอร์เซ็นต์:</span>
                    <span class="detail-value">{{ number_format($goodsReceipt->milestone_percentage, 1) }}%</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">สถานะตรวจสอบ:</span>
                    <span class="detail-value"><span class="status-badge">{{ $goodsReceipt->inspection_status_label }}</span></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">สถานะ:</span>
                    <span class="detail-value"><span class="status-badge">{{ $goodsReceipt->status_label }}</span></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่สร้าง:</span>
                    <span class="detail-value">{{ $goodsReceipt->created_at ? $goodsReceipt->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
            </div>
            
            <div class="creator-info">
                <strong>👤 ผู้สร้าง:</strong> {{ $creator->name }}<br>
                <strong>📧 อีเมล:</strong> {{ $creator->email }}<br>
                <strong>⏰ เวลาที่สร้าง:</strong> {{ $goodsReceipt->created_at ? $goodsReceipt->created_at->format('d/m/Y เวลา H:i น.') : 'เพิ่งสร้าง' }}
            </div>
            
            @if($goodsReceipt->notes)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>📝 หมายเหตุ:</strong><br>
                {{ $goodsReceipt->notes }}
            </div>
            @endif
            
            @if($goodsReceipt->inspection_notes)
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>🔍 หมายเหตุการตรวจสอบ:</strong><br>
                {{ $goodsReceipt->inspection_notes }}
            </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/admin/goods-receipts/{{ $goodsReceipt->id }}" class="action-button">
                    🔍 ดูรายละเอียดใบตรวจรับ
                </a>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <h4 style="color: #17a2b8; margin-bottom: 15px;">📋 ขั้นตอนต่อไป</h4>
                <ul style="color: #6c757d; padding-left: 20px;">
                    <li><strong>ตรวจสอบรายละเอียด:</strong> กรุณาเข้าสู่ระบบและตรวจสอบรายละเอียดของใบตรวจรับงาน/วัสดุ</li>
                    <li><strong>ประสานกับผู้ขาย:</strong> ติดต่อประสานเรื่องการส่งมอบกับผู้ขายหากจำเป็น</li>
                    <li><strong>ตรวจรับสินค้า/งาน:</strong> ดำเนินการตรวจรับสินค้าหรืองานให้เป็นไปตามมาตรฐาน</li>
                    <li><strong>บันทึกผลการตรวจสอบ:</strong> อัปเดตสถานะและบันทึกผลการตรวจสอบในระบบ</li>
                    <li><strong>แจ้งผลการตรวจรับ:</strong> แจ้งผลการตรวจรับให้กับผู้เกี่ยวข้อง</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <div class="company-logo">🏢 INNOBIC</div>
            <p>ระบบจัดการตรวจรับงาน/วัสดุ<br>
            อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            <p style="font-size: 12px; color: #adb5bd;">
                หากคุณมีคำถามใดๆ กรุณาติดต่อทีมจัดซื้อ หรือ IT Support
            </p>
        </div>
    </div>
</body>
</html>