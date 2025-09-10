<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Innobic') }} - @yield('title', 'หน้าหลัก')</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset(path: 'assets/img/innobic.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <!-- Inline JavaScript เพื่อแก้ปัญหา sidebar ไม่แสดงผลทันที -->
        <script>
            // ทำงานก่อน DOM จะโหลดเสร็จ
            (function() {
                // กำหนด sidebar ให้แสดงเสมอโดยไม่มีเงื่อนไข
                document.cookie = "showSidebar=true; path=/";
                sessionStorage.setItem('sidebarActive', 'true');
                
                // ตรวจสอบว่าเป็นหน้า dashboard หรือไม่
                const path = window.location.pathname;
                const isDashboard = path === '/dashboard' || path === '/' || path.includes('dashboard');
                
                // ถ้าเป็นหน้า dashboard ให้เพิ่ม class สำหรับ CSS
                if (isDashboard) {
                    document.documentElement.classList.add('dashboard-page');
                    document.body.classList.add('dashboard');
                }
                
                // ตรวจสอบและเพิ่ม class active เมื่อโหลดหน้าเสร็จ
                window.addEventListener('load', function() {
                    var sidebar = document.getElementById('sidebar');
                    var content = document.getElementById('content');
                    
                    if (sidebar) sidebar.classList.add('active');
                    if (content) content.classList.add('active');
                    
                    // เพิ่ม class สำหรับหน้า dashboard อีกครั้ง
                    if (isDashboard) {
                        document.documentElement.classList.add('dashboard-page');
                        document.body.classList.add('dashboard');
                    }
                });
                
                // ตรวจสอบและเพิ่ม class active ทันทีที่ DOM พร้อม
                document.addEventListener('DOMContentLoaded', function() {
                    var sidebar = document.getElementById('sidebar');
                    var content = document.getElementById('content');
                    
                    if (sidebar) sidebar.classList.add('active');
                    if (content) content.classList.add('active');
                    
                    // เพิ่ม class สำหรับหน้า dashboard อีกครั้ง
                    if (isDashboard) {
                        document.documentElement.classList.add('dashboard-page');
                        document.body.classList.add('dashboard');
                    }
                });
            })();
        </script>

        <style>
            :root {
                --primary: #0f6db7;
                --primary-light: #3a8fd0;
                --primary-dark: #0a518a;
                --primary-hover: #0e62a3;
                --bg-light: #f5f8fa;
                --sidebar-width: 260px;
                --header-height: 70px;
                --card-radius: 12px;
            }
            
            body {
                font-family: 'Sarabun', sans-serif;
                background-color: var(--bg-light);
                color: #333;
                overflow-x: hidden;
            }
            
            /* Sidebar Styles */
            #sidebar {
                width: var(--sidebar-width);
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                background: white;
                border-right: 1px solid rgba(0,0,0,0.1);
                transition: all 0.3s;
                z-index: 1010; /* เพิ่ม z-index เพื่อให้อยู่ด้านหน้าเสมอ */
                box-shadow: 0 0 15px rgba(0,0,0,0.05);
                overflow-y: auto; /* เพิ่มการเลื่อนในกรณีที่มีเมนูมากเกินความสูงหน้าจอ */
                /* สำคัญ: ลบ margin-left ที่เป็นลบออกไปเพื่อให้แสดงเสมอ */
            }
            
            /* กำหนดค่าเริ่มต้นให้ sidebar แสดงตลอดเวลา */
            #sidebar.active {
                margin-left: 0 !important;
            }
            
            #sidebar .sidebar-header {
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 15px;
            }
            
            #sidebar .sidebar-header img {
                height: 40px;
            }
            
            #sidebar ul li a {
                padding: 12px 20px;
                display: flex;
                align-items: center;
                color: #666;
                font-weight: 500;
                border-radius: 8px;
                margin: 4px 10px;
                transition: all 0.3s;
            }
            
            #sidebar ul li a i {
                font-size: 18px;
                min-width: 32px;
            }
            
            #sidebar ul li a:hover, 
            #sidebar ul li a.active {
                background-color: rgba(15, 109, 183, 0.08);
                color: var(--primary);
            }
            
            #sidebar ul li a.active {
                background-color: rgba(15, 109, 183, 0.12);
                font-weight: 600;
            }
            
            #sidebar ul li a .badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }
            
            /* Collapsible Menu Styles */
            .menu-group {
                margin: 4px 10px;
            }
            
            .menu-group-toggle {
                padding: 12px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                color: #666;
                font-weight: 500;
                border-radius: 8px;
                transition: all 0.3s;
                cursor: pointer;
                background: none;
                border: none;
                width: 100%;
                text-align: left;
            }
            
            .menu-group-toggle:hover {
                background-color: rgba(15, 109, 183, 0.08);
                color: var(--primary);
            }
            
            .menu-group-toggle.active {
                background-color: rgba(15, 109, 183, 0.12);
                color: var(--primary);
                font-weight: 600;
            }
            
            .menu-group-toggle i.fa-chevron-down {
                transition: transform 0.3s;
                font-size: 12px;
            }
            
            .menu-group-toggle.collapsed i.fa-chevron-down {
                transform: rotate(-90deg);
            }
            
            .menu-group-content {
                overflow: hidden;
                transition: max-height 0.3s ease-out;
                max-height: 0;
            }
            
            .menu-group-content.show {
                max-height: 500px;
            }
            
            .menu-group-content ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .menu-group-content ul li a {
                padding: 10px 20px 10px 40px;
                font-size: 0.9rem;
                margin: 2px 0;
            }
            
            .menu-group-badge {
                background-color: var(--primary);
                color: white;
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
                border-radius: 10px;
                margin-left: auto;
                margin-right: 10px;
            }
            
            /* Content Styles */
            #content {
                width: calc(100% - var(--sidebar-width));
                min-height: 100vh;
                position: absolute;
                right: 0;
                transition: all 0.3s;
            }
            
            /* เมื่อ sidebar ถูกปิดบนหน้าจอใหญ่ */
            @media (min-width: 992px) {
                #sidebar:not(.active) {
                    margin-left: calc(-1 * var(--sidebar-width));
                }
                
                #content.active {
                    width: calc(100% - var(--sidebar-width));
                }
                
                #content:not(.active) {
                    width: 100%;
                    margin-left: 0;
                }
            }
            
            .top-header {
                height: var(--header-height);
                background-color: white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.03);
                display: flex;
                align-items: center;
                padding: 0 15px;
                position: sticky;
                top: 0;
                z-index: 1020;
                border-bottom: 1px solid rgba(0,0,0,0.1);
            }
            
            @media (min-width: 768px) {
                .top-header {
                    padding: 0 25px;
                }
            }
            
            .user-profile {
                display: flex;
                align-items: center;
                margin-left: auto;
            }
            
            .user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background-color: var(--primary);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                margin-right: 10px;
            }
            
            /* Company Selector Styles */
            .company-logo-sm {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                object-fit: cover;
            }
            
            .company-logo-xs {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                object-fit: cover;
            }
            
            .company-switch-item {
                padding: 10px 16px !important;
                transition: all 0.2s ease;
            }
            
            .company-switch-item:hover {
                background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
            }
            
            /* Main Content */
            .main-content {
                padding: 30px;
            }
            
            .page-title {
                margin-bottom: 25px;
                font-weight: 600;
                color: #2d3748;
            }
            
            /* Card Styles */
            .content-card {
                background: white;
                border-radius: var(--card-radius);
                box-shadow: 0 2px 15px rgba(0,0,0,0.04);
                margin-bottom: 20px;
                border: 1px solid rgba(0,0,0,0.05);
                overflow: hidden;
            }
            
            .card-header {
                padding: 20px;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                background-color: white;
            }
            
            .card-header h5 {
                margin-bottom: 0;
                font-weight: 600;
                color: #2d3748;
            }
            
            .card-body {
                padding: 20px;
            }
            
            /* Form Controls */
            .form-control, .form-select {
                border-radius: 8px;
                border-color: #e2e8f0;
                padding: 10px 16px;
            }
            
            .form-control:focus, .form-select:focus {
                border-color: var(--primary-light);
                box-shadow: 0 0 0 3px rgba(15, 109, 183, 0.1);
            }
            
            .form-label {
                font-weight: 500;
                margin-bottom: 8px;
                color: #4a5568;
            }
            
            /* Button Styles */
            .btn {
                border-radius: 8px;
                padding: 10px 16px;
                font-weight: 500;
                transition: all 0.3s;
            }
            
            .btn-primary {
                background-color: var(--primary);
                border-color: var(--primary);
            }
            
            .btn-primary:hover, .btn-primary:focus {
                background-color: var(--primary-hover);
                border-color: var(--primary-hover);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(15, 109, 183, 0.2);
            }
            
            .btn-secondary {
                background-color: #718096;
                border-color: #718096;
            }
            
            .btn-outline-primary {
                color: var(--primary);
                border-color: var(--primary);
            }
            
            .btn-outline-primary:hover {
                background-color: var(--primary);
                color: white;
                transform: translateY(-2px);
            }
            
            /* Table Styles */
            .table {
                width: 100%;
            }
            
            .table th {
                font-weight: 600;
                color: #4a5568;
                border-bottom-width: 2px;
            }
            
            .table td {
                vertical-align: middle;
            }
            
            /* Badge Styles */
            .badge {
                padding: 5px 10px;
                border-radius: 30px;
                font-weight: 500;
            }
            
            /* Alert Styles */
            .alert {
                border-radius: 8px;
                border: none;
                padding: 16px;
            }
            
            /* Responsive Adjustments */
            @media (max-width: 991.98px) {
                #sidebar {
                    margin-left: calc(-1 * var(--sidebar-width));
                    transition: all 0.3s ease;
                    position: fixed;
                    top: 0;
                    left: 0;
                    z-index: 1030;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
                }
                
                /* ปรับให้ sidebar แสดงเมื่อมี class active */
                #sidebar.active {
                    margin-left: 0;
                    z-index: 1030;
                }
                
                #content {
                    width: 100%;
                    margin-left: 0;
                    position: relative;
                    right: auto;
                    left: 0;
                    transition: all 0.3s;
                }
                
                #content.active {
                    width: 100%;
                    position: relative;
                }
                
                .sidebar-overlay {
                    display: none;
                    position: fixed;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 1025;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    top: 0;
                    left: 0;
                }
                
                .sidebar-overlay.active {
                    display: block;
                    opacity: 1;
                }

                /* เพิ่มสไตล์สำหรับการแสดงผลเมนูในโหมดมือถือ */
                #sidebar ul li a {
                    padding: 15px 20px;
                    font-size: 16px;
                }
                
                /* ปรับขนาดปุ่มปิด sidebar */
                #sidebarClose {
                    padding: 8px;
                    color: #fff !important;
                    opacity: 0.8;
                    transition: opacity 0.2s;
                }
                
                #sidebarClose:hover {
                    opacity: 1;
                    color: #fff !important;
                }
                
                /* ปรับ top-header สำหรับ mobile */
                .top-header {
                    padding: 0 10px;
                }
                
                .main-content {
                    padding: 15px;
                }
                
                /* ซ่อน company name บนมือถือ */
                .company-selector .btn span {
                    display: none;
                }
                
                .user-profile span {
                    display: none;
                }
            }
            
            @media (max-width: 767.98px) {
                .main-content {
                    padding: 20px;
                }
            }
            
            /* Enhanced Vendor Status Badges */
            .badge.bg-warning {
                background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%) !important;
                color: #fff !important;
                font-weight: 600;
                box-shadow: 0 3px 10px rgba(255, 152, 0, 0.4);
                border: none;
            }
            
            .badge.bg-success {
                background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%) !important;
                color: #fff !important;
                font-weight: 600;
                box-shadow: 0 3px 10px rgba(76, 175, 80, 0.4);
                border: none;
            }
            
            .badge.bg-danger {
                background: linear-gradient(135deg, #ef5350 0%, #f44336 100%) !important;
                color: #fff !important;
                font-weight: 600;
                box-shadow: 0 3px 10px rgba(244, 67, 54, 0.4);
                border: none;
            }
            
            .badge.bg-secondary {
                background: linear-gradient(135deg, #8e8e93 0%, #6c757d 100%) !important;
                color: #fff !important;
                font-weight: 600;
                box-shadow: 0 3px 10px rgba(108, 117, 125, 0.4);
                border: none;
            }
            
            .badge.badge-lg {
                font-size: 0.85rem !important;
                padding: 0.5rem 0.75rem !important;
                border-radius: 0.5rem !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 0.3rem !important;
                letter-spacing: 0.5px !important;
                text-transform: none !important;
                font-family: 'Sarabun', sans-serif !important;
                transition: all 0.3s ease !important;
            }
            
            .badge.badge-lg:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
        </style>
        
        @stack('styles')
    </head>
    <body class="{{ request()->routeIs('dashboard') || request()->is('/') ? 'dashboard' : '' }}">
        
        <div class="wrapper">
            <!-- Livewire Notification Component - Temporarily disabled -->
            {{-- @livewire('notification-bar') --}}
            
            <!-- Sidebar Overlay -->
            <div class="sidebar-overlay"></div>
            
            <!-- Sidebar - เพิ่ม class active เพื่อให้แสดงผลตั้งแต่เริ่มต้น -->
            <nav id="sidebar" class="active">
                <div class="sidebar-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <img src="{{ asset(path: 'assets/img/innobic.png') }}" alt="Innobic Logo">
                        <!-- ปุ่มปิด sidebar สำหรับมือถือ -->
                        <button type="button" id="sidebarClose" class="btn btn-link text-white d-lg-none">
                            <i class="fas fa-times fa-lg"></i>
                        </button>
                    </div>
                </div>

                <ul class="list-unstyled components">
                    @php
                        $user = auth()->user();
                        $canApprove = false;
                        $pendingPOCount = 0;
                        $pendingPRCount = 0;
                        
                        if ($user) {
                            $canApprove = $user->isAdmin() || 
                                         $user->hasRole('procurement_manager') || 
                                         $user->hasRole('department_head');
                        }
                        
                        if ($canApprove && $user) {
                            try {
                                // PO Pending Count
                                $pendingPOCount = \App\Models\PurchaseOrder::where('status', 'pending_approval')
                                    ->when($user->hasRole('department_head') && !$user->isAdmin() && !$user->hasRole('procurement_manager'), function($query) use ($user) {
                                        return $query->where('department_id', $user->department_id);
                                    })
                                    ->count();
                                
                                // PR Pending Count
                                $pendingPRCount = \App\Models\PurchaseRequisition::where('status', 'pending')
                                    ->when($user->hasRole('department_head') && !$user->isAdmin() && !$user->hasRole('procurement_manager'), function($query) use ($user) {
                                        return $query->where('department_id', $user->department_id);
                                    })
                                    ->count();
                            } catch (\Exception $e) {
                                $pendingPOCount = 0;
                                $pendingPRCount = 0;
                            }
                        }
                    @endphp
                    
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-chart-pie"></i> แดชบอร์ด
                        </a>
                    </li>
                    @if(auth()->user()->roles->contains('name', 'admin'))
                    <li>
                        <a href="{{ route('filament.admin.resources.users.index') }}" class="{{ request()->routeIs('filament.admin.resources.users.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i> จัดการพนักงาน
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                            <i class="fas fa-user"></i> โปรไฟล์
                        </a>
                    </li>
                    <!-- Purchase Requisition Menu Group -->
                    <li class="menu-group">
                        <button class="menu-group-toggle {{ request()->routeIs('purchase-requisitions.*') ? 'active' : 'collapsed' }}" 
                                data-target="pr-menu" 
                                aria-expanded="{{ request()->routeIs('purchase-requisitions.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-invoice me-3"></i>
                                <span>Purchase Requisition (PR)</span>
                            </div>
                            <div class="d-flex align-items-center">
                                @if($pendingPRCount > 0)
                                    <span class="menu-group-badge">{{ $pendingPRCount }}</span>
                                @endif
                                <i class="fas fa-chevron-down ms-2"></i>
                            </div>
                        </button>
                        <div class="menu-group-content {{ request()->routeIs('purchase-requisitions.*') ? 'show' : '' }}" id="pr-menu">
                            <ul>
                                @if(auth()->user()->isAdmin())
                                <li>
                                    <a href="{{ route('purchase-requisitions.index') }}" 
                                       class="{{ request()->routeIs('purchase-requisitions.index') || request()->routeIs('purchase-requisitions.show') || request()->routeIs('purchase-requisitions.create') || request()->routeIs('purchase-requisitions.edit') ? 'active' : '' }}">
                                        <i class="fas fa-list me-2"></i> จัดการใบ PR
                                    </a>
                                </li>
                                @endif

                                @if(!auth()->user()->isAdmin())
                                <li>
                                    <a href="{{ route('purchase-requisitions.my-requests') }}" 
                                       class="{{ request()->routeIs('purchase-requisitions.my-requests') ? 'active' : '' }}">
                                        <i class="fas fa-user-edit me-2"></i> ใบ PR ของฉัน
                                    </a>
                                </li>
                                @endif

                                @if($canApprove)
                                <li>
                                    <a href="{{ route('purchase-requisitions.pending-approvals') }}" 
                                       class="{{ request()->routeIs('purchase-requisitions.pending-approvals') ? 'active' : '' }}">
                                        <i class="fas fa-clock me-2"></i> PR รอการอนุมัติ
                                        @if($pendingPRCount > 0)
                                            <span class="badge bg-info text-white ms-2">{{ $pendingPRCount }}</span>
                                        @endif
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>



                    <!-- Purchase Order Menu Group -->
                    <li class="menu-group">
                        <button class="menu-group-toggle {{ request()->routeIs('purchase-orders.*') ? 'active' : 'collapsed' }}" 
                                data-target="po-menu" 
                                aria-expanded="{{ request()->routeIs('purchase-orders.*') ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-alt me-3"></i>
                                <span>Purchase Order (PO)</span>
                            </div>
                            <div class="d-flex align-items-center">
                                @if($pendingPOCount > 0)
                                    <span class="menu-group-badge">{{ $pendingPOCount }}</span>
                                @endif
                                <i class="fas fa-chevron-down ms-2"></i>
                            </div>
                        </button>
                        <div class="menu-group-content {{ request()->routeIs('purchase-orders.*') ? 'show' : '' }}" id="po-menu">
                            <ul>
                                <li>
                                    <a href="{{ route('purchase-orders.index') }}" 
                                       class="{{ request()->routeIs('purchase-orders.index') || request()->routeIs('purchase-orders.show') || request()->routeIs('purchase-orders.create') || request()->routeIs('purchase-orders.edit') ? 'active' : '' }}">
                                        <i class="fas fa-list me-2"></i> จัดการใบ PO
                                    </a>
                                </li>

                                @if($canApprove)
                                <li>
                                    <a href="{{ route('purchase-orders.pending-approvals') }}" 
                                       class="{{ request()->routeIs('purchase-orders.pending-approvals') ? 'active' : '' }}">
                                        <i class="fas fa-clock me-2"></i> PO รอการอนุมัติ
                                        @if($pendingPOCount > 0)
                                            <span class="badge bg-warning text-dark ms-2">{{ $pendingPOCount }}</span>
                                        @endif
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>

                    <!-- เมนูจัดการผู้ขาย -->
                    <li>
                        <a href="{{ route('vendors.index') }}" class="{{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                            <i class="fas fa-store"></i> จัดการผู้ขาย
                        </a>
                    </li>

                    <!-- Value Analysis Menu -->
                    <li>
                        <a href="{{ route('value-analysis.index') }}" class="{{ request()->routeIs('value-analysis.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i> Value Analysis (VA)
                        </a>
                    </li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                            </a>
                        </form>
                    </li>
                </ul>
            </nav>

            <!-- Page Content - เพิ่ม class active เช่นกัน -->
            <div id="content" class="active">
                <div class="top-header">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-primary">
                        <i class="fas fa-bars"></i>
                        <span class="d-none d-sm-inline ms-2">เมนู</span>
                    </button>
                    
                    @if(isset($currentCompany))
                        <div class="company-selector ms-auto me-3">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="companyDropdown">
                                    <img src="{{ $currentCompany->getLogoUrl() }}" alt="{{ $currentCompany->display_name }}" class="company-logo-sm me-2">
                                    <span>{{ $currentCompany->display_name }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="companyDropdown">
                                    @foreach(App\Models\Company::getActiveCompanies() as $company)
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center company-switch-item" 
                                               href="#" 
                                               data-company-id="{{ $company->id }}"
                                               @if($company->id == $currentCompany->id) style="background-color: rgba(var(--bs-primary-rgb), 0.1);" @endif>
                                                <img src="{{ $company->getLogoUrl() }}" alt="{{ $company->display_name }}" class="company-logo-xs me-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $company->display_name }}</div>
                                                    <small class="text-muted">{{ $company->name }}</small>
                                                </div>
                                                @if($company->id == $currentCompany->id)
                                                    <i class="fas fa-check text-primary ms-auto"></i>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-secondary" href="{{ route('company.select') }}">
                                            <i class="fas fa-cog me-2"></i>
                                            จัดการบริษัท
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                    
                    <div class="user-profile">
                        <div class="user-avatar">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                </div>

                <div class="main-content">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarCollapse = document.getElementById('sidebarCollapse');
                const sidebarClose = document.getElementById('sidebarClose');
                const sidebar = document.getElementById('sidebar');
                const content = document.getElementById('content');
                const overlay = document.querySelector('.sidebar-overlay');
                
                // ตรวจสอบขนาดหน้าจอและจัดการ sidebar
                function initializeSidebar() {
                    const savedState = sessionStorage.getItem('sidebarActive');
                    const shouldShow = savedState !== null ? savedState === 'true' : window.innerWidth > 991.98;
                    
                    if (shouldShow) {
                        sidebar.classList.add('active');
                        content.classList.add('active');
                    } else {
                        sidebar.classList.remove('active');
                        content.classList.remove('active');
                    }
                    
                    // ปิด overlay และ unlock scroll เสมอ
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                // เรียกใช้เมื่อโหลดหน้าแล้ว
                initializeSidebar();
                
                // ฟังก์ชันสำหรับแสดง/ซ่อน sidebar
                function toggleSidebar() {
                    const isActive = sidebar.classList.contains('active');
                    
                    if (isActive) {
                        // ปิด sidebar
                        sidebar.classList.remove('active');
                        content.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    } else {
                        // เปิด sidebar
                        sidebar.classList.add('active');
                        content.classList.add('active');
                        
                        // แสดง overlay เฉพาะบนมือถือ
                        if (window.innerWidth <= 991.98) {
                            overlay.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }
                    }
                    
                    // บันทึกสถานะ sidebar ไว้ใน sessionStorage
                    sessionStorage.setItem('sidebarActive', sidebar.classList.contains('active'));
                }
                
                // ทำให้ sidebar แสดงเมื่อมีการคลิกที่เมนูใน sidebar
                document.querySelectorAll('#sidebar ul li a').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        // ถ้าเป็นการคลิกที่เมนู dashboard ให้ทำสิ่งต่อไปนี้
                        if (this.getAttribute('href') && this.getAttribute('href').includes('dashboard')) {
                            // เพิ่ม class dashboard ให้กับ body
                            document.body.classList.add('dashboard');
                            document.documentElement.classList.add('dashboard-page');
                            
                            // กำหนดค่า sessionStorage และ cookie
                            sessionStorage.setItem('sidebarActive', 'true');
                            document.cookie = "showSidebar=true; path=/";
                            
                            // แสดง sidebar และปรับตำแหน่ง content
                            sidebar.classList.add('active');
                            content.classList.add('active');
                        }
                        
                        if (window.innerWidth <= 991.98) {
                            setTimeout(function() {
                                if (!sidebar.classList.contains('active')) {
                                    toggleSidebar();
                                }
                            }, 150);
                        }
                    });
                });
                
                // เพิ่ม event listener พิเศษสำหรับลิงก์ dashboard
                var dashboardLinks = document.querySelectorAll('a[href*="dashboard"], a[href="/"]');
                dashboardLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        // เพิ่ม class dashboard ให้กับ body
                        document.body.classList.add('dashboard');
                        document.documentElement.classList.add('dashboard-page');
                        
                        // เพิ่มค่า sessionStorage และ cookie
                        sessionStorage.setItem('sidebarActive', 'true');
                        document.cookie = "showSidebar=true; path=/";
                        
                        // แสดง sidebar และปรับตำแหน่ง content
                        sidebar.classList.add('active');
                        content.classList.add('active');
                    });
                });
                
                // ฟังก์ชันอัพเดทไอคอนปุ่ม toggle
                function updateToggleIcon() {
                    const icon = sidebarCollapse.querySelector('i');
                    const textSpan = sidebarCollapse.querySelector('span');
                    
                    if (window.innerWidth <= 991.98) {
                        // บนมือถือ: แสดง hamburger menu เสมอ (สำหรับเปิดเมนู)
                        icon.className = 'fas fa-bars';
                        if (textSpan) textSpan.textContent = 'เมนู';
                    } else {
                        // บนคอมพิวเตอร์: เปลี่ยนไอคอนตามสถานะ sidebar
                        if (sidebar.classList.contains('active')) {
                            icon.className = 'fas fa-times';
                            if (textSpan) textSpan.textContent = 'ปิดเมนู';
                        } else {
                            icon.className = 'fas fa-bars';
                            if (textSpan) textSpan.textContent = 'เมนู';
                        }
                    }
                }
                
                // เพิ่ม event listener สำหรับปุ่ม hamburger
                sidebarCollapse.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                    updateToggleIcon();
                });
                
                // อัพเดทไอคอนเมื่อเริ่มต้น
                updateToggleIcon();
                
                // เพิ่ม event listener สำหรับปุ่มปิด sidebar (มือถือ)
                if (sidebarClose) {
                    sidebarClose.addEventListener('click', function(e) {
                        e.preventDefault();
                        toggleSidebar();
                        updateToggleIcon();
                    });
                }
                
                // เพิ่ม event listener สำหรับ overlay เพื่อปิด sidebar เมื่อคลิกพื้นที่ว่าง
                overlay.addEventListener('click', toggleSidebar);
                
                // เพิ่ม event listener สำหรับ window resize
                window.addEventListener('resize', function() {
                    // ถ้าเปลี่ยนจากมือถือเป็นคอมพิวเตอร์ ให้ปิด overlay
                    if (window.innerWidth > 991.98) {
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                    // ถ้าเปลี่ยนจากคอมพิวเตอร์เป็นมือถือ และ sidebar เปิดอยู่ ให้แสดง overlay
                    else if (window.innerWidth <= 991.98 && sidebar.classList.contains('active')) {
                        overlay.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    
                    // อัพเดทไอคอนปุ่ม toggle
                    updateToggleIcon();
                });
                
                // ปิด sidebar เมื่อคลิกลิงก์ในมือถือเท่านั้น
                document.querySelectorAll('#sidebar ul li a').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        if (window.innerWidth <= 991.98 && sidebar.classList.contains('active')) {
                            setTimeout(function() {
                                sidebar.classList.remove('active');
                                content.classList.remove('active');
                                overlay.classList.remove('active');
                                document.body.style.overflow = '';
                                sessionStorage.setItem('sidebarActive', 'false');
                            }, 200);
                        }
                    });
                });
                
                // Collapsible Menu Functions
                function initCollapsibleMenus() {
                    const menuToggles = document.querySelectorAll('.menu-group-toggle');
                    
                    menuToggles.forEach(function(toggle) {
                        toggle.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            const targetId = this.getAttribute('data-target');
                            const targetContent = document.getElementById(targetId);
                            const chevronIcon = this.querySelector('.fa-chevron-down');
                            
                            if (targetContent && chevronIcon) {
                                // Toggle collapsed class
                                this.classList.toggle('collapsed');
                                
                                // Toggle content visibility
                                targetContent.classList.toggle('show');
                                
                                // Update aria-expanded
                                const isExpanded = targetContent.classList.contains('show');
                                this.setAttribute('aria-expanded', isExpanded);
                                
                                // Save state to localStorage
                                localStorage.setItem(`menu-${targetId}`, isExpanded ? 'expanded' : 'collapsed');
                            }
                        });
                    });
                    
                    // Restore saved menu states
                    menuToggles.forEach(function(toggle) {
                        const targetId = toggle.getAttribute('data-target');
                        const targetContent = document.getElementById(targetId);
                        const savedState = localStorage.getItem(`menu-${targetId}`);
                        const isCurrentlyActive = toggle.classList.contains('active');
                        
                        // If this menu group is currently active (user is on a related page), always expand
                        if (isCurrentlyActive) {
                            toggle.classList.remove('collapsed');
                            targetContent.classList.add('show');
                            toggle.setAttribute('aria-expanded', 'true');
                            localStorage.setItem(`menu-${targetId}`, 'expanded');
                        } else {
                            // Use saved state or default to collapsed
                            if (savedState === 'expanded') {
                                toggle.classList.remove('collapsed');
                                targetContent.classList.add('show');
                                toggle.setAttribute('aria-expanded', 'true');
                            } else {
                                toggle.classList.add('collapsed');
                                targetContent.classList.remove('show');
                                toggle.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
                
                // Initialize collapsible menus
                initCollapsibleMenus();

                // Auto-adjust for screen size
                function checkWidth() {
                    // ตรวจสอบว่าเคยบันทึกสถานะ sidebar ไว้หรือไม่
                    const savedSidebarState = sessionStorage.getItem('sidebarActive');
                    
                    if (window.innerWidth <= 991.98) {
                        // สำหรับมือถือ โดยปกติจะไม่แสดง sidebar ยกเว้นถ้ามีการคลิกที่ปุ่ม
                        // หรือถ้ามีการบันทึกสถานะไว้ว่า active ให้แสดง
                        if (savedSidebarState === 'true') {
                            sidebar.classList.add('active');
                            content.classList.add('active');
                            overlay.classList.add('active');
                        } else {
                            sidebar.classList.remove('active');
                            content.classList.remove('active');
                            overlay.classList.remove('active');
                            document.body.style.overflow = '';
                        }
                    } else {
                        // สำหรับหน้าจอขนาดใหญ่ แสดง sidebar เสมอ
                        sidebar.classList.add('active');
                        content.classList.add('active');
                    }
                }
                
                // ตรวจสอบหาก cookie หรือ sessionStorage มีค่าที่ระบุว่าควรแสดง sidebar
                const cookies = document.cookie.split(';').map(cookie => cookie.trim());
                const showSidebarCookie = cookies.find(cookie => cookie.startsWith('showSidebar='));
                
                if (showSidebarCookie || sessionStorage.getItem('sidebarActive') === 'true') {
                    sidebar.classList.add('active');
                    content.classList.add('active');
                    if (window.innerWidth <= 991.98) {
                        overlay.classList.add('active');
                    }
                }
                
                // ทำการ check ครั้งแรกเมื่อโหลดหน้า
                checkWidth();
                
                // สำหรับหน้า dashboard โดยเฉพาะ - ทำให้ sidebar แสดงเสมอด้วย setTimeout
                const isDashboard = window.location.pathname === '/dashboard' || window.location.pathname === '/' || window.location.pathname.includes('dashboard');
                if (isDashboard) {
                    // ถ้าเป็นหน้า dashboard ให้ forced refresh sidebar อีกครั้งหลังจากโหลดหน้าเสร็จ
                    sidebar.classList.add('active');
                    content.classList.add('active');
                    sessionStorage.setItem('sidebarActive', 'true');
                    
                    setTimeout(function() {
                        sidebar.classList.add('active');
                        content.classList.add('active');
                    }, 300);
                }
                
                // ตรวจสอบทุกครั้งที่ resize หน้าจอ
                window.addEventListener('resize', checkWidth);
                
                // Auto-close alerts after 5 seconds
                setTimeout(function() {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(function(alert) {
                        if (typeof bootstrap !== 'undefined') {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        } else {
                            // Fallback if bootstrap is not loaded
                            alert.style.display = 'none';
                        }
                    });
                }, 5000);
                
                // Company switching functionality
                document.querySelectorAll('.company-switch-item').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const companyId = this.getAttribute('data-company-id');
                        const currentCompanyId = document.querySelector('.company-switch-item[style*="background-color"]')?.getAttribute('data-company-id');
                        
                        if (companyId === currentCompanyId) {
                            return; // ถ้าเป็นบริษัทเดียวกันไม่ต้องทำอะไร
                        }
                        
                        // แสดง loading state
                        const button = document.getElementById('companyDropdown');
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังเปลี่ยน...';
                        button.disabled = true;
                        
                        // ส่ง request เปลี่ยนบริษัท
                        fetch('{{ route("company.switch") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                company_id: companyId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // แสดงข้อความสำเร็จ
                                const alert = document.createElement('div');
                                alert.className = 'alert alert-success alert-dismissible fade show';
                                alert.innerHTML = `
                                    <i class="fas fa-check-circle me-2"></i>${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                `;
                                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
                                
                                // Reload หน้าเพื่อใช้งานบริษัทใหม่
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                throw new Error(data.error || 'เกิดข้อผิดพลาด');
                            }
                        })
                        .catch(error => {
                            // แสดงข้อความผิดพลาด
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-danger alert-dismissible fade show';
                            alert.innerHTML = `
                                <i class="fas fa-exclamation-circle me-2"></i>${error.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
                            
                            // คืนค่า button เดิม
                            button.innerHTML = originalText;
                            button.disabled = false;
                        });
                    });
                });
            });
        </script>
        
        <!-- Bootstrap JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Fix for company dropdown -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Initializing company dropdown...');
                
                const companyDropdown = document.getElementById('companyDropdown');
                if (!companyDropdown) {
                    console.log('Company dropdown not found');
                    return;
                }
                
                const dropdownMenu = companyDropdown.nextElementSibling;
                if (!dropdownMenu) {
                    console.log('Dropdown menu not found');
                    return;
                }
                
                console.log('Company dropdown elements found, setting up handlers...');
                
                // Manual dropdown toggle
                companyDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Company dropdown clicked');
                    
                    // Toggle show class
                    const isOpen = dropdownMenu.classList.contains('show');
                    
                    // Close all other dropdowns first
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    
                    // Toggle this dropdown
                    if (!isOpen) {
                        dropdownMenu.classList.add('show');
                        console.log('Dropdown opened');
                    } else {
                        dropdownMenu.classList.remove('show');
                        console.log('Dropdown closed');
                    }
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!companyDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
                
                // Prevent dropdown from closing when clicking inside
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                console.log('Company dropdown initialized successfully');
            });
        </script>
        
        @livewireScripts
        @stack('scripts')
    </body>
</html>
