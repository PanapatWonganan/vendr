<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Innobic') }} - @yield('title', 'เลือกบริษัท')</title>
    
    <link rel="icon" href="{{ asset('assets/img/innobic.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #1f2937;
            line-height: 1.6;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 32px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .company-header img {
            height: 40px;
            margin-bottom: 16px;
        }

        .company-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .company-header p {
            font-size: 14px;
            color: #6b7280;
        }

        .company-select-container {
            width: 100%;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .companies-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .company-item {
            position: relative;
        }

        .company-item input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .company-card {
            display: flex;
            align-items: center;
            padding: 16px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .company-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .company-item input[type="radio"]:checked + .company-card {
            border-color: #2563eb;
            background: #f8fafc;
        }

        .company-logo {
            margin-right: 14px;
        }

        .company-logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
            color: inherit;
        }

        .company-desc {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        .current-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #dcfce7;
            color: #166534;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
        }

        .select-indicator {
            color: #9ca3af;
            font-size: 14px;
        }

        .company-item input[type="radio"]:checked + .company-card .select-indicator {
            color: #2563eb;
        }

        .btn-submit {
            width: 100%;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }

        .btn-logout {
            width: 100%;
            padding: 10px 16px;
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #374151;
        }

        .no-companies {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
        }

        .no-companies i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .no-companies h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111827;
        }

        .no-companies p {
            font-size: 14px;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }
            
            .main-wrapper {
                max-width: 100%;
            }
            
            .company-header {
                padding: 20px;
                margin-bottom: 24px;
            }
            
            .company-header h1 {
                font-size: 20px;
            }
            
            .company-card {
                padding: 14px;
            }
            
            .company-logo img {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="company-header">
            <img src="{{ asset('assets/img/innobic.png') }}" alt="Innobic">
            <h1>เลือกบริษัท</h1>
            <p>เลือกบริษัทที่ต้องการเข้าใช้งานระบบ</p>
        </div>
        
        @yield('content')
    </div>
</body>
</html>