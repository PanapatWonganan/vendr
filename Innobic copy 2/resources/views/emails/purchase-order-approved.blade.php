<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบ PO ได้รับการอนุมัติ</title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .po-details h3 {
            margin-top: 0;
            color: #28a745;
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
            background-color: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .approver-info {
            background-color: #e8f5e8;
            border: 1px solid #c3e6c3;
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
            <div class="icon">✅</div>
            <h1>ใบ PO ได้รับการอนุมัติแล้ว</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                สวัสดี{{ $recipient ? 'คุณ ' . $recipient->name : '' }},
            </div>
            
            @if($recipient)
                {{-- Message for internal recipients (PO creator, inspection committee) --}}
                <p>เรามีความยินดีที่จะแจ้งให้ทราบว่า ใบ Purchase Order ของคุณได้รับการอนุมัติเรียบร้อยแล้ว</p>
            @else
                {{-- Message for vendor --}}
                <p>เรามีความยินดีที่จะแจ้งให้ทราบว่า บริษัทฯ ได้อนุมัติใบ Purchase Order แล้ว และขอให้ท่านดำเนินการตามรายละเอียดดังต่อไปนี้</p>
            @endif
            
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
                    <span class="detail-value"><span class="status-badge">อนุมัติแล้ว</span></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่สร้าง:</span>
                    <span class="detail-value">{{ $purchaseOrder->created_at ? $purchaseOrder->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่อนุมัติ:</span>
                    <span class="detail-value">{{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('d/m/Y H:i') : 'เพิ่งอนุมัติ' }}</span>
                </div>

                @if(!$recipient && $purchaseOrder->contact_name)
                {{-- Additional contact info for vendor --}}
                <div class="detail-row">
                    <span class="detail-label">ผู้ติดต่อ:</span>
                    <span class="detail-value">{{ $purchaseOrder->contact_name }}</span>
                </div>
                
                @if($purchaseOrder->contact_email)
                <div class="detail-row">
                    <span class="detail-label">อีเมลผู้ติดต่อ:</span>
                    <span class="detail-value">{{ $purchaseOrder->contact_email }}</span>
                </div>
                @endif
                @endif
            </div>
            
            <div class="approver-info">
                <strong>👤 ผู้อนุมัติ:</strong> {{ $approver->name }}<br>
                <strong>📧 อีเมล:</strong> {{ $approver->email }}<br>
                <strong>⏰ เวลาที่อนุมัติ:</strong> {{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('d/m/Y เวลา H:i น.') : 'เพิ่งอนุมัติ' }}
            </div>
            
            @if($purchaseOrder->notes)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>📝 หมายเหตุ:</strong><br>
                {{ $purchaseOrder->notes }}
            </div>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="action-button">
                    🔍 ดูรายละเอียดใบ PO
                </a>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <h4 style="color: #28a745; margin-bottom: 15px;">📋 ขั้นตอนต่อไป</h4>
                @if($recipient)
                    {{-- Steps for internal recipients --}}
                    <ul style="color: #6c757d; padding-left: 20px;">
                        <li>ใบ PO ของคุณพร้อมสำหรับการส่งให้ผู้ขายแล้ว</li>
                        <li>ทีมจัดซื้อจะดำเนินการส่ง PO ให้ผู้ขายต่อไป</li>
                        <li>คุณจะได้รับการแจ้งเตือนเมื่อผู้ขายยืนยันรับ PO</li>
                        <li>ติดตามสถานะการจัดส่งสินค้าผ่านระบบ</li>
                        @if($recipient->id == ($purchaseOrder->purchaseRequisition->inspection_committee_id ?? null))
                            <li><strong>สำหรับคณะกรรมการตรวจรับ:</strong> โปรดเตรียมตัวสำหรับการตรวจรับสินค้าเมื่อได้รับแจ้งจากผู้ขาย</li>
                        @endif
                    </ul>
                @else
                    {{-- Steps for vendor --}}
                    <ul style="color: #6c757d; padding-left: 20px;">
                        <li><strong>ยืนยันการรับ PO:</strong> กรุณายืนยันการรับใบ PO นี้ภายใน 2 วันทำการ</li>
                        <li><strong>เตรียมสินค้า/บริการ:</strong> ดำเนินการจัดเตรียมสินค้าหรือบริการตามรายละเอียดในใบ PO</li>
                        <li><strong>ประสานการส่งมอบ:</strong> ติดต่อประสานเรื่องวันที่และเวลาส่งมอบกับเจ้าหน้าที่ที่ระบุในใบ PO</li>
                        <li><strong>จัดส่งใบกำกับภาษี:</strong> จัดส่งใบกำกับภาษีหลังจากส่งมอบสินค้าเรียบร้อยแล้ว</li>
                        <li><strong>ติดตาม:</strong> หากมีคำถามสามารถติดต่อเจ้าหน้าที่ผู้ติดต่อได้โดยตรง</li>
                    </ul>
                @endif
            </div>
        </div>
        
        <div class="footer">
            <div class="company-logo">🏢 INNOBIC</div>
            <p>ระบบจัดการ Purchase Order<br>
            อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            <p style="font-size: 12px; color: #adb5bd;">
                หากคุณมีคำถามใดๆ กรุณาติดต่อทีมจัดซื้อ หรือ IT Support
            </p>
        </div>
    </div>
</body>
</html> 