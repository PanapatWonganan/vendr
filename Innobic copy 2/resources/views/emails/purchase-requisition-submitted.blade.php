<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบขอซื้อได้ถูกส่งขออนุมัติ</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .alert {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .details {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .details h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 120px;
        }
        .detail-value {
            color: #495057;
            text-align: right;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
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
        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }
        .priority-urgent {
            color: #fd7e14;
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-medium {
            color: #ffc107;
            font-weight: bold;
        }
        .priority-low {
            color: #28a745;
        }
        @media only screen and (max-width: 600px) {
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
    <div class="container">
        <div class="header">
            <h1>🔔 ใบขอซื้อได้ถูกส่งขออนุมัติ</h1>
        </div>

        <div class="content">
            <p>สวัสดี คุณ{{ $recipient->name }},</p>
            
            <div class="alert">
                <strong>มีใบขอซื้อใหม่ที่ต้องการการอนุมัติจากคุณ</strong><br>
                ใบขอซื้อหมายเลข <strong>{{ $purchaseRequisition->pr_number }}</strong> ได้ถูกส่งขออนุมัติแล้ว
            </div>

            <div class="details">
                <h3>📋 รายละเอียดใบขอซื้อ</h3>
                
                <div class="detail-row">
                    <span class="detail-label">หมายเลข PR:</span>
                    <span class="detail-value"><strong>{{ $purchaseRequisition->pr_number }}</strong></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">หัวข้อ:</span>
                    <span class="detail-value">{{ $purchaseRequisition->title }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ผู้ขอ:</span>
                    <span class="detail-value">{{ $purchaseRequisition->requester->name ?? 'N/A' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">แผนก:</span>
                    <span class="detail-value">{{ $purchaseRequisition->department->name ?? 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">จำนวนเงิน:</span>
                    <span class="detail-value">{{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">ความสำคัญ:</span>
                    <span class="detail-value">
                        @switch($purchaseRequisition->priority)
                            @case('urgent')
                                <span class="priority-urgent">ด่วนมาก</span>
                                @break
                            @case('high')
                                <span class="priority-high">สูง</span>
                                @break
                            @case('medium')
                                <span class="priority-medium">ปานกลาง</span>
                                @break
                            @case('low')
                                <span class="priority-low">ต่ำ</span>
                                @break
                            @default
                                {{ $purchaseRequisition->priority }}
                        @endswitch
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">วันที่ขอ:</span>
                    <span class="detail-value">{{ $purchaseRequisition->request_date ? $purchaseRequisition->request_date->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ส่งโดย:</span>
                    <span class="detail-value">{{ $submittedBy->name }} ({{ $submittedBy->email }})</span>
                </div>
                
                @if($purchaseRequisition->notes)
                <div class="detail-row">
                    <span class="detail-label">หมายเหตุ:</span>
                    <span class="detail-value">{{ $purchaseRequisition->notes }}</span>
                </div>
                @endif
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/purchase-requisitions/{{ $purchaseRequisition->id }}" class="button">
                    🔍 ดูรายละเอียดและอนุมัติ
                </a>
            </div>

            <p><strong>หมายเหตุ:</strong> กรุณาตรวจสอบและดำเนินการอนุมัติภายในเวลาที่เหมาะสม เพื่อไม่ให้เกิดความล่าช้าในกระบวนการจัดซื้อ</p>
        </div>

        <div class="footer">
            <p>อีเมลนี้ถูกส่งโดยอัตโนมัติจากระบบ Innobic<br>
            หากมีคำถามกรุณาติดต่อทีมงาน IT</p>
            <p style="font-size: 12px; color: #999;">
                © {{ date('Y') }} Innobic. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html> 