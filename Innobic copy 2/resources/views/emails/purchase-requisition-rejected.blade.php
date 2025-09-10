<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การขอซื้อถูกปฏิเสธ</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
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
            border-left: 4px solid #dc3545;
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
            background-color: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .rejection-reason {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .rejection-reason h3 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .rejection-reason p {
            color: #856404;
            margin: 0;
            font-style: italic;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
        .next-steps {
            background-color: #cce5ff;
            border: 1px solid #80bfff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #0066cc;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .next-steps ul {
            color: #0066cc;
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
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
            <div class="icon">❌</div>
            <h1>การขอซื้อถูกปฏิเสธ</h1>
            <p>Purchase Requisition Rejected</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                เรียน คุณ{{ $requester->name }},
            </div>
            
            <p>เราขออภัยที่ต้องแจ้งให้ทราบว่า <strong>การขอซื้อของคุณไม่ได้รับการอนุมัติ</strong> โปรดตรวจสอบรายละเอียดด้านล่าง</p>
            
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
                    <span class="info-label">ผู้ปฏิเสธ:</span>
                    <span class="info-value">{{ $rejectedBy->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">วันที่ปฏิเสธ:</span>
                    <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">สถานะ:</span>
                    <span class="info-value"><span class="status">ปฏิเสธ</span></span>
                </div>
            </div>
            
            <div class="rejection-reason">
                <h3>🔍 เหตุผลในการปฏิเสธ</h3>
                <p>"{{ $rejectionReason }}"</p>
            </div>
            
            @if($purchaseRequisition->description)
            <p><strong>รายละเอียดการขอซื้อเดิม:</strong></p>
            <p style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                {{ $purchaseRequisition->description }}
            </p>
            @endif
            
            <div class="next-steps">
                <h3>📋 ขั้นตอนถัดไป</h3>
                <ul>
                    <li>ตรวจสอบเหตุผลในการปฏิเสธเพื่อปรับปรุงการขอซื้อ</li>
                    <li>ติดต่อผู้จัดการหรือหัวหน้าแผนกเพื่อปรึกษาเพิ่มเติม</li>
                    <li>สร้างการขอซื้อใหม่พร้อมข้อมูลที่ปรับปรุงแล้ว</li>
                    <li>รอโอกาสที่เหมาะสมในการยื่นขอใหม่</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/purchase-requisitions/{{ $purchaseRequisition->id }}" class="button">
                    ดูรายละเอียดเต็ม
                </a>
            </div>
            
            <div style="background-color: #e8f5e8; border: 1px solid #b3e0b3; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; color: #2d5a2d;">
                    <strong>💡 คำแนะนำ:</strong> หากคุณมีคำถามเกี่ยวกับการปฏิเสธนี้ 
                    กรุณาติดต่อ{{ $rejectedBy->name }}หรือหัวหน้าแผนกของคุณเพื่อขอคำปรึกษา
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