<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOR ได้รับการอนุมัติ</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
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
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .alert {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
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
            min-width: 140px;
        }
        .detail-value {
            color: #495057;
            text-align: right;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: 600;
            text-align: center;
        }
        .button-secondary {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: 600;
            text-align: center;
        }
        .next-steps {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .next-steps h4 {
            margin-top: 0;
            color: #1d4ed8;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 5px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        @media only screen and (max-width: 600px) {
            .detail-row { flex-direction: column; }
            .detail-value { text-align: left; margin-top: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TOR ได้รับการอนุมัติแล้ว</h1>
            <p>ขอบเขตของงาน (Terms of Reference)</p>
        </div>

        <div class="content">
            <p>สวัสดี คุณ{{ $creator->name }},</p>

            <div class="alert">
                <strong>TOR ของคุณได้รับการอนุมัติแล้ว</strong><br>
                TOR หมายเลข <strong>{{ $tor->tor_number }}</strong> ได้รับการอนุมัติเรียบร้อยแล้ว
            </div>

            <div class="details">
                <h3>รายละเอียด TOR</h3>

                <div class="detail-row">
                    <span class="detail-label">หมายเลข TOR:</span>
                    <span class="detail-value"><strong>{{ $tor->tor_number }}</strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ชื่อ TOR:</span>
                    <span class="detail-value">{{ $tor->title }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ประเภท:</span>
                    <span class="detail-value">{{ $torTypeLabel }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">งบประมาณ:</span>
                    <span class="detail-value">{{ number_format($tor->budget_estimate ?? 0, 2) }} {{ $tor->currency }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">อนุมัติโดย:</span>
                    <span class="detail-value">{{ $approver->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">วันที่อนุมัติ:</span>
                    <span class="detail-value">{{ $tor->approved_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
                </div>

                @if($comments)
                <div class="detail-row">
                    <span class="detail-label">หมายเหตุ:</span>
                    <span class="detail-value">{{ $comments }}</span>
                </div>
                @endif
            </div>

            <div class="next-steps">
                <h4>ขั้นตอนถัดไป</h4>
                <ul>
                    <li>สร้างใบขอซื้อ (PR) จาก TOR นี้</li>
                    <li>ระบบจะนำข้อมูลจาก TOR ไปกรอกให้อัตโนมัติ</li>
                    <li>ตรวจสอบและส่ง PR เพื่อดำเนินการจัดซื้อจัดจ้าง</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/tor-builder/{{ $tor->id }}/preview" class="button">
                    ดู TOR
                </a>
                <a href="{{ config('app.url') }}/admin/purchase-requisitions/create?tor_id={{ $tor->id }}" class="button-secondary">
                    สร้าง PR จาก TOR
                </a>
            </div>
        </div>

        <div class="footer">
            <p>อีเมลนี้ถูกส่งโดยอัตโนมัติจากระบบ VENDR<br>
            หากมีคำถามกรุณาติดต่อทีมงาน IT</p>
            <p style="font-size: 12px; color: #999;">
                &copy; {{ date('Y') }} Innobic. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
