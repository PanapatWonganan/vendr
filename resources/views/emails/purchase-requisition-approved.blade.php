<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การขอซื้อได้รับอนุมัติ</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .pr-info {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            width: 40%;
        }
        .info-value {
            color: #212529;
            width: 60%;
            text-align: right;
        }
        .status {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label, .info-value {
                width: 100%;
                text-align: left;
            }
            .info-value {
                font-weight: 600;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">✅</div>
            <h1>การขอซื้อได้รับอนุมัติ</h1>
            <p>Purchase Requisition Approved</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                เรียน คุณ{{ $requester->name }},
            </div>
            
            <p>เรามีความยินดีที่จะแจ้งให้ทราบว่า <strong>การขอซื้อของคุณได้รับการอนุมัติแล้ว</strong></p>
            
            <div class="pr-info">
                <div class="info-row">
                    <span class="info-label">หมายเลข PR:</span>
                    <span class="info-value"><strong>{{ $purchaseRequisition->pr_number }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">หัวข้อ:</span>
                    <span class="info-value">{{ $purchaseRequisition->title }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">แผนก:</span>
                    <span class="info-value">{{ $purchaseRequisition->department->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">วันที่ต้องการ:</span>
                    <span class="info-value">{{ $purchaseRequisition->required_date ? $purchaseRequisition->required_date->format('d/m/Y') : 'ไม่ระบุ' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">จำนวนเงิน:</span>
                    <span class="info-value">{{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ผู้อนุมัติ:</span>
                    <span class="info-value">{{ $approver->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">วันที่อนุมัติ:</span>
                    <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">สถานะ:</span>
                    <span class="info-value"><span class="status">อนุมัติแล้ว</span></span>
                </div>
            </div>
            
            @if($purchaseRequisition->description)
            <p><strong>รายละเอียด:</strong></p>
            <p style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                {{ $purchaseRequisition->description }}
            </p>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/purchase-requisitions/{{ $purchaseRequisition->id }}" class="button">
                    ดูรายละเอียดเต็ม
                </a>
            </div>
            
            <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; color: #0066cc;">
                    <strong>ขั้นตอนถัดไป:</strong> การขอซื้อของคุณจะถูกดำเนินการต่อในกระบวนการจัดซื้อ 
                    คุณจะได้รับการแจ้งเตือนเมื่อมีการอัพเดทสถานะ
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Innobic Management System</strong></p>
            <p>อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            <p style="margin-top: 15px;">
                หากคุณไม่ต้องการรับอีเมลแจ้งเตือน สามารถปรับการตั้งค่าได้ใน
                <a href="{{ config('app.url') }}/profile/edit" style="color: #007bff;">โปรไฟล์ของคุณ</a>
            </p>
        </div>
    </div>
</body>
</html> 