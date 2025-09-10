<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Innobic') }} - @yield('title', 'เลือกบริษัท')</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('assets/img/innobic.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --primary: #2563eb;
                --primary-light: #3b82f6;
                --primary-dark: #1d4ed8;
                --secondary: #64748b;
                --success: #10b981;
                --background: #f8fafc;
                --surface: #ffffff;
                --border: #e2e8f0;
                --text-primary: #1e293b;
                --text-secondary: #64748b;
                --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            }
            
            * {
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: var(--background);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                color: var(--text-primary);
                line-height: 1.6;
            }

            .main-wrapper {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
            }

            .company-header {
                text-align: center;
                margin-bottom: 3rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .company-header img {
                height: 48px;
                margin-bottom: 1.5rem;
                filter: drop-shadow(0 1px 2px rgb(0 0 0 / 0.1));
                display: block;
            }

            .company-header h1 {
                font-size: 1.875rem;
                font-weight: 700;
                color: var(--text-primary);
                margin-bottom: 0.5rem;
                letter-spacing: -0.025em;
            }

            .company-header p {
                font-size: 1rem;
                color: var(--text-secondary);
                margin-bottom: 0;
                font-weight: 400;
            }

            .company-card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 0.75rem;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: var(--shadow);
            }

            .company-card:hover {
                transform: translateY(-1px);
                box-shadow: var(--shadow-lg);
                border-color: var(--primary-light);
            }

            .btn-check:checked + .company-card {
                background: var(--surface);
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1), var(--shadow-lg);
                transform: translateY(-1px);
            }

            .company-logo img {
                width: 48px;
                height: 48px;
                border-radius: 0.5rem;
                object-fit: cover;
                border: 1px solid var(--border);
            }

            .company-name {
                font-size: 1.125rem;
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.25rem;
            }

            .company-description {
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-bottom: 0;
            }

            .status-badge {
                background: var(--success);
                color: white;
                padding: 0.25rem 0.5rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            .primary-button {
                background: var(--primary);
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 600;
                font-size: 1rem;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                cursor: pointer;
            }

            .primary-button:hover {
                background: var(--primary-dark);
                transform: translateY(-1px);
                box-shadow: var(--shadow-lg);
                color: white;
            }

            .secondary-button {
                background: transparent;
                color: var(--text-secondary);
                border: 1px solid var(--border);
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-weight: 500;
                font-size: 0.875rem;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                cursor: pointer;
            }

            .secondary-button:hover {
                background: var(--background);
                border-color: var(--secondary);
                color: var(--text-primary);
            }

            .alert {
                border-radius: 0.5rem;
                border: none;
                font-weight: 500;
            }

            .alert-success {
                background: rgb(220 252 231);
                color: rgb(21 128 61);
            }

            .alert-danger {
                background: rgb(254 226 226);
                color: rgb(185 28 28);
            }

            .no-companies {
                text-align: center;
                padding: 3rem 1rem;
                color: var(--text-secondary);
            }

            .no-companies i {
                font-size: 3rem;
                margin-bottom: 1rem;
                color: var(--border);
            }

            @media (max-width: 768px) {
                .main-wrapper {
                    padding: 1rem;
                }
                
                .company-header h1 {
                    font-size: 1.5rem;
                }
                
                .company-header {
                    margin-bottom: 2rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="main-wrapper">
            <div class="container">
                <div class="company-header">
                    <img src="{{ asset('assets/img/innobic.png') }}" alt="Innobic">
                    <h1>เลือกบริษัท</h1>
                    <p>เลือกบริษัทที่ต้องการเข้าใช้งานระบบ</p>
                </div>
                
                @yield('content')
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html> 