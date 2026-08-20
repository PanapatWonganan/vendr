<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOR ใหม่รอพิจารณา</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
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
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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
        .priority-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .priority-urgent { background: #fef2f2; color: #dc2626; }
        .priority-high { background: #fff7ed; color: #ea580c; }
        .priority-medium { background: #fefce8; color: #ca8a04; }
        .priority-low { background: #f0fdf4; color: #16a34a; }
        @media only screen and (max-width: 600px) {
            .detail-row { flex-direction: column; }
            .detail-value { text-align: left; margin-top: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TOR ใหม่รอพิจารณา</h1>
            <p>ขอบเขตของงาน (Terms of Reference)</p>
        </div>

        <div class="content">
            <p>สวัสดี คุณ{{ $recipient->name }},</p>

            <div class="alert">
                <strong>มี TOR ใหม่ที่ต้องการการพิจารณาจากคุณ</strong><br>
                TOR หมายเลข <strong>{{ $tor->tor_number }}</strong> ได้ถูกส่งพิจารณาแล้ว
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
                    <span class="detail-label">แผนก:</span>
                    <span class="detail-value">{{ $tor->department->name ?? '-' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">งบประมาณ:</span>
                    <span class="detail-value">{{ number_format($tor->budget_estimate ?? 0, 2) }} {{ $tor->currency }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ลำดับสำคัญ:</span>
                    <span class="detail-value">
                        <span class="priority-badge priority-{{ $tor->priority }}">{{ $priorityLabel }}</span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">ส่งโดย:</span>
                    <span class="detail-value">{{ $submitter->name }} ({{ $submitter->email }})</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">วันที่ส่ง:</span>
                    <span class="detail-value">{{ $tor->submitted_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/tor-builder/{{ $tor->id }}/preview" class="button">
                    ดูรายละเอียดและพิจารณา
                </a>
            </div>

            <p><strong>หมายเหตุ:</strong> กรุณาตรวจสอบและดำเนินการพิจารณาภายในเวลาที่เหมาะสม</p>
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
