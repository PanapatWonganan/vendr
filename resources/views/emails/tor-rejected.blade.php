<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOR ไม่ได้รับการอนุมัติ</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
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
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-box {
            background-color: #fef9c3;
            border: 1px solid #fde047;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .reason-box h4 {
            margin-top: 0;
            color: #92400e;
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .tips {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .tips h4 {
            margin-top: 0;
            color: #1d4ed8;
        }
        .tips ul {
            margin: 0;
            padding-left: 20px;
        }
        .tips li {
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
            <h1>TOR ไม่ได้รับการอนุมัติ</h1>
            <p>ขอบเขตของงาน (Terms of Reference)</p>
        </div>

        <div class="content">
            <p>สวัสดี คุณ{{ $creator->name }},</p>

            <div class="alert">
                <strong>TOR ของคุณไม่ได้รับการอนุมัติ</strong><br>
                TOR หมายเลข <strong>{{ $tor->tor_number }}</strong> ถูกปฏิเสธ กรุณาตรวจสอบเหตุผลด้านล่าง
            </div>

            <div class="reason-box">
                <h4>เหตุผลที่ไม่อนุมัติ</h4>
                <p>{{ $reason }}</p>
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
                    <span class="detail-label">ไม่อนุมัติโดย:</span>
                    <span class="detail-value">{{ $rejector->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">วันที่:</span>
                    <span class="detail-value">{{ $tor->rejected_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="tips">
                <h4>คำแนะนำ</h4>
                <ul>
                    <li>ตรวจสอบเหตุผลที่ไม่อนุมัติอย่างละเอียด</li>
                    <li>แก้ไข TOR ตามข้อเสนอแนะ</li>
                    <li>ใช้ AI ตรวจสอบ TOR ก่อนส่งพิจารณาอีกครั้ง</li>
                    <li>ส่ง TOR เพื่อพิจารณาอีกครั้ง</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/admin/terms-of-references/{{ $tor->id }}/edit" class="button">
                    แก้ไข TOR
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
