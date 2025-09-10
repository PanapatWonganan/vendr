@extends('layouts.app')

@section('title', 'แดชบอร์ด')

@push('styles')

    <style>
        /* CSS Variables for Theme */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: rgba(0,0,0,0.05);
            --shadow-color: rgba(0,0,0,0.04);
            --card-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-primary: #1a202c;
            --bg-secondary: #2d3748;
            --text-primary: #f7fafc;
            --text-secondary: #e2e8f0;
            --text-muted: #a0aec0;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.3);
            --card-bg: #2d3748;
        }

        /* Apply theme variables */
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .card, .stat-card, .quick-action-card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .legend-container {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }


        /* Quick Actions Styling */
        .dropdown-menu {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        .dropdown-item {
            color: var(--text-primary);
        }

        .dropdown-item:hover {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        /* Floating Action Button */
        .fab-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            background-color: var(--primary-dark);
        }

        .fab-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            display: none;
            flex-direction: column;
            gap: 10px;
        }

        .fab-menu.show {
            display: flex;
        }

        .fab-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--card-bg);
            padding: 12px 16px;
            border-radius: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
        }

        .fab-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: var(--text-primary);
        }

        .fab-item i {
            width: 24px;
            text-align: center;
        }
    </style>

    <style>
        /* 🎨 Modern Dashboard Title */
        .dashboard-title {
            margin-bottom: var(--spacing-xl);
            font-weight: 700;
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }
        
        .dashboard-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }
        
        .welcome-message {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-sm);
            font-weight: 500;
            margin-top: var(--spacing-sm);
        }
        
        /* 🎨 Modern Stat Cards */
        .stat-card {
            background: var(--bg-glass-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xl);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            height: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* 🎨 Modern Stat Icons & Values */
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: var(--radius-2xl);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--spacing-lg);
            background: var(--gradient-primary);
            box-shadow: var(--shadow-primary);
            position: relative;
            overflow: hidden;
        }
        
        
        .stat-icon i {
            font-size: 28px;
            color: white;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-change {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .stat-change.positive {
            color: #48bb78;
        }
        
        .stat-change.negative {
            color: #e53e3e;
        }
        
        .primary-bg {
            background-color: var(--primary);
        }
        
        .primary-light-bg {
            background-color: var(--primary-light);
        }
        
        .primary-dark-bg {
            background-color: var(--primary-dark);
        }
        
        /* 🎨 Modern Quick Action Cards */
        .quick-action-card {
            background: var(--bg-glass-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xl);
            margin-top: var(--spacing-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
        }
        
        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
        }
        
        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .quick-action-title {
            font-weight: 600;
            margin-bottom: var(--spacing-lg);
            color: var(--text-primary);
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.25rem;
        }
        
        /* 🎨 Modern Action Buttons */
        .action-btn {
            background: var(--bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: var(--color-primary);
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: var(--radius-xl);
            padding: var(--spacing-lg);
            transition: all var(--transition-normal);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            min-height: 80px;
            text-decoration: none;
        }
        
        .action-btn:hover {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-1px);
            box-shadow: var(--shadow-primary);
        }
        
        .action-btn i {
            margin-right: var(--spacing-sm);
            font-size: 1.2rem;
        }
        
        /* Calendar Styles */
        .event-item {
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .event-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .event-item.priority-urgent {
            border-left: 4px solid #e53e3e;
            background-color: #fed7d7;
        }
        
        .event-item.priority-high {
            border-left: 4px solid #ff8c00;
            background-color: #ffeaa7;
        }
        
        .event-item.priority-medium {
            border-left: 4px solid #4299e1;
            background-color: #bee3f8;
        }
        
        .event-item.priority-low {
            border-left: 4px solid #38a169;
            background-color: #c6f6d5;
        }
        
        .event-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .event-item.priority-urgent .event-icon { background-color: #e53e3e; }
        .event-item.priority-high .event-icon { background-color: #ff8c00; }
        .event-item.priority-medium .event-icon { background-color: #4299e1; }
        .event-item.priority-low .event-icon { background-color: #38a169; }
        
        .badge-urgent { background-color: #e53e3e; color: white; }
        .badge-high { background-color: #ff8c00; color: white; }
        .badge-medium { background-color: #4299e1; color: white; }
        .badge-low { background-color: #38a169; color: white; }
        
        /* Mini Stats */
        .mini-stat {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .mini-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .mini-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
        }
        
        .mini-stat-content h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .mini-stat-content span {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 500;
        }
        
        /* Drag & Drop Calendar Enhancements */
        .fc-event {
            transition: opacity 0.2s ease, transform 0.1s ease;
            border-radius: 4px !important;
        }
        
        .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .fc-event.fc-event-dragging {
            opacity: 0.8;
            transform: rotate(5deg) scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            z-index: 999;
        }
        
        .fc-day-today {
            background-color: var(--bg-secondary) !important;
            border: 2px solid #007bff !important;
        }
        
        .fc-event-title {
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        /* Drag handle indicator */
        .fc-event::before {
            content: '⋮⋮';
            position: absolute;
            left: 3px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: rgba(255,255,255,0.7);
            line-height: 0.8;
            pointer-events: none;
        }
        
        /* Enhanced tooltip styles */
        .fc-event[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0,0,0,0.9);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
        }
        
        /* Calendar view responsiveness for drag & drop */
        @media (max-width: 768px) {
            .fc-event {
                min-height: 22px;
                font-size: 0.75rem;
            }
            
            .fc-event-title {
                font-size: 0.7rem;
            }
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .col-md-8, .col-md-2 {
                margin-bottom: 10px;
            }
            
            .event-item .row {
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="dashboard-title mb-1">แดชบอร์ด</h4>
        <p class="welcome-message mb-0">สวัสดี, {{ auth()->user()->name }} ยินดีต้อนรับสู่ระบบจัดการ Innobic</p>
    </div>
    
    <!-- Quick Actions -->
    <div class="d-flex align-items-center gap-3">
        
        <!-- Dashboard Customization -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-cog me-1"></i> ปรับแต่ง
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">การแสดงผล</h6></li>
                <li>
                    <div class="dropdown-item-text">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showStats" checked>
                            <label class="form-check-label" for="showStats">แสดงสถิติ</label>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="dropdown-item-text">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showCalendar" checked>
                            <label class="form-check-label" for="showCalendar">แสดงปฏิทิน</label>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="dropdown-item-text">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showQuickActions" checked>
                            <label class="form-check-label" for="showQuickActions">แสดงการดำเนินการด่วน</label>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">ปฏิทิน</h6></li>
                <li>
                    <div class="dropdown-item-text">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPO" checked>
                            <label class="form-check-label" for="showPO">แสดง PO</label>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="dropdown-item-text">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPR" checked>
                            <label class="form-check-label" for="showPR">แสดง PR</label>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" id="resetDashboard">
                    <i class="fas fa-undo text-warning me-2"></i> รีเซ็ตการตั้งค่า
                </a></li>
            </ul>
        </div>

        <!-- Quick Actions Dropdown -->
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bolt me-1"></i> การดำเนินการด่วน
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('purchase-requisitions.create-direct-small') }}">
                    <i class="fas fa-shopping-cart text-success me-2"></i> PR จัดซื้อตรง ≤10,000
                </a></li>
                <li><a class="dropdown-item" href="{{ route('purchase-requisitions.create-direct-medium') }}">
                    <i class="fas fa-shopping-basket text-warning me-2"></i> PR จัดซื้อตรง ≤100,000
                </a></li>
                <li><a class="dropdown-item" href="{{ route('purchase-orders.create') }}">
                    <i class="fas fa-file-alt text-primary me-2"></i> สร้าง PO ใหม่
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('vendors.index') }}">
                    <i class="fas fa-store text-info me-2"></i> จัดการผู้ขาย
                </a></li>
            </ul>
        </div>
    </div>
</div>
                
                <!-- Stats Overview -->
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon primary-bg">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <p class="stat-label">ใบ PO ทั้งหมด</p>
                            <h3 class="stat-value">{{ $totalPOs }}</h3>
                            <span class="stat-change positive">
                                <i class="fas fa-chart-line"></i> ทั้งหมด
                            </span>
                        </div>
                    </div>
                    
                    @if($myPendingApprovals > 0)
                    <div class="col-md-3">
                        <div class="stat-card" style="border-left: 4px solid #e53e3e;">
                            <div class="stat-icon" style="background-color: #e53e3e;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <p class="stat-label">รอการอนุมัติ (ของฉัน)</p>
                            <h3 class="stat-value" style="color: #e53e3e;">{{ $myPendingApprovals }}</h3>
                            <span class="stat-change" style="color: #e53e3e;">
                                <i class="fas fa-exclamation-triangle"></i> ต้องดำเนินการ
                            </span>
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #ff8c00;">
                                <i class="fas fa-edit"></i>
                            </div>
                            <p class="stat-label">ร่าง</p>
                            <h3 class="stat-value">{{ $draftPOs }}</h3>
                            <span class="stat-change">
                                <i class="fas fa-pencil-alt"></i> ยังไม่ส่งอนุมัติ
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #38a169;">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <p class="stat-label">ใบ PR ทั้งหมด</p>
                            <h3 class="stat-value">{{ $totalPRs }}</h3>
                            <span class="stat-change positive">
                                <i class="fas fa-chart-line"></i> รวม Direct: {{ $directPurchasePRs }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Calendar Section -->
                <div class="row g-4 mt-4">
                    <div class="col-lg-8">
                        <div class="quick-action-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="quick-action-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>กำหนดการส่งงานที่สำคัญ
                                </h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-view="list">รายการ</button>
                                    <button type="button" class="btn btn-outline-primary active" data-view="calendar">ปฏิทิน</button>
                                </div>
                            </div>
                            
                            <!-- List View (Hidden by default) -->
                            <div id="listView" class="calendar-view" style="display: none;">
                                @if($calendarEvents->isEmpty())
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">ไม่มีกำหนดการที่สำคัญในช่วง 30 วันข้างหน้า</p>
                                    </div>
                                @else
                                    @foreach($calendarEvents->take(10) as $event)
                                    <div class="event-item priority-{{ $event['priority'] }} mb-2 p-3 rounded">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center">
                                                    <div class="event-icon me-3">
                                                        @if($event['type'] === 'po_delivery')
                                                            <i class="fas fa-truck"></i>
                                                        @elseif($event['type'] === 'pr_required')
                                                            <i class="fas fa-clock"></i>
                                                        @elseif($event['type'] === 'po_overdue')
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">{{ $event['title'] }}</h6>
                                                        <small class="text-muted">{{ $event['description'] }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <span class="badge badge-{{ $event['priority'] }}">
                                                    @if($event['days_until'] < 0)
                                                        เลย {{ abs($event['days_until']) }} วัน
                                                    @elseif($event['days_until'] == 0)
                                                        วันนี้
                                                    @else
                                                        {{ $event['days_until'] }} วัน
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    
                                    @if($calendarEvents->count() > 10)
                                    <div class="text-center mt-3">
                                        <button class="btn btn-outline-primary btn-sm" id="showMoreEvents">
                                            แสดงเพิ่มเติม ({{ $calendarEvents->count() - 10 }} รายการ)
                                        </button>
                                    </div>
                                    @endif
                                @endif
                            </div>
                            
                            <!-- Calendar View (Default view) -->
                            <div id="calendarView" class="calendar-view">
                                <!-- Calendar Legend -->
                                <div class="calendar-legend mb-3">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="legend-container p-3 bg-light rounded">
                                                <h6 class="legend-title mb-2">
                                                    <i class="fas fa-info-circle me-2"></i>คำอธิบายสี
                                                </h6>
                                                <div class="row mb-3">
                                                    <div class="col-12">
                                                        <strong class="text-primary">📋 PO (กำหนดส่งของ)</strong>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-po-urgent"></span>
                                                            <small class="legend-text">ด่วนมาก (≤3 วัน)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-po-high"></span>
                                                            <small class="legend-text">ด่วน (4-7 วัน)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-po-normal"></span>
                                                            <small class="legend-text">ปกติ (>7 วัน)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-12">
                                                        <strong class="text-success">📝 PR (กำหนดต้องการ)</strong>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-pr-urgent"></span>
                                                            <small class="legend-text">ด่วนมาก (≤3 วัน)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-pr-high"></span>
                                                            <small class="legend-text">ด่วน (4-7 วัน)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-6 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-pr-normal"></span>
                                                            <small class="legend-text">ปกติ (>7 วัน)</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-2">
                                                        <div class="legend-item">
                                                            <span class="legend-color bg-overdue"></span>
                                                            <small class="legend-text"><strong>⚠️ เกินกำหนดแล้ว</strong></small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-12">
                                                        <div class="legend-icons">
                                                            <span class="legend-icon-item me-3">
                                                                <i class="fas fa-truck text-primary me-1"></i>
                                                                <small>กำหนดส่งของ PO</small>
                                                            </span>
                                                            <span class="legend-icon-item me-3">
                                                                <i class="fas fa-clock text-warning me-1"></i>
                                                                <small>กำหนดต้องการ PR</small>
                                                            </span>
                                                            <span class="legend-icon-item">
                                                                <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                                                <small>เกินกำหนดแล้ว</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats for PR -->
                    <div class="col-lg-4">
                        <div class="quick-action-card">
                            <h5 class="quick-action-title">สถิติ PR</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-stat-icon" style="background-color: #ff8c00;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="mini-stat-content">
                                            <h4>{{ $pendingPRs }}</h4>
                                            <span>รออนุมัติ</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-stat-icon" style="background-color: #38a169;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="mini-stat-content">
                                            <h4>{{ $completedPRs }}</h4>
                                            <span>เสร็จสิ้น</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-stat-icon" style="background-color: #4299e1;">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div class="mini-stat-content">
                                            <h4>{{ $directPurchasePRs }}</h4>
                                            <span>จัดซื้อตรง</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat">
                                        <div class="mini-stat-icon" style="background-color: #667eea;">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="mini-stat-content">
                                            <h4>{{ $totalUsers }}</h4>
                                            <span>พนักงาน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-action-card">
                    <h5 class="quick-action-title">การดำเนินการที่รวดเร็ว</h5>
                    <div class="row g-3">
                        @if(auth()->user()->roles->contains('name', 'admin'))
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('admin.users.index') }}" class="action-btn d-block">
                                <i class="fas fa-user-plus"></i> เพิ่มพนักงานใหม่
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="#" class="action-btn d-block">
                                <i class="fas fa-building"></i> จัดการแผนก
                            </a>
                        </div>
                        @endif
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('profile.edit') }}" class="action-btn d-block">
                                <i class="fas fa-cog"></i> ตั้งค่าโปรไฟล์
                            </a>
                        </div>
                        @if(auth()->user()->roles->contains('name', 'requester') || auth()->user()->roles->contains('name', 'admin'))
                                                  {{-- 
                          <div class="col-md-3 col-sm-6">
                              <a href="{{ route('purchase-requisitions.create') }}" class="action-btn d-block">
                                  <i class="fas fa-file-invoice"></i> สร้างใบขอซื้อใหม่
                              </a>
                          </div>
                                                     --}}
                          @endif



                          <div class="col-md-3 col-sm-6">
                              <a href="{{ route('purchase-orders.create') }}" class="action-btn d-block">
                                  <i class="fas fa-file-alt"></i> สร้างใบ PO ใหม่
                              </a>
                          </div>

                          <div class="col-md-3 col-sm-6">
                            <a href="#" class="action-btn d-block">
                                <i class="fas fa-question-circle"></i> ช่วยเหลือ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Demo Section for Livewire Components -->
                <div class="quick-action-card">
                    <h5 class="quick-action-title">Demo: Livewire & Alpine.js Components</h5>
                    
                    <!-- Notification Demo Buttons -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-sm-6">
                            <button onclick="showSuccess('การดำเนินการสำเร็จ!')" 
                                    class="action-btn d-block" style="background-color: #48bb78; color: white;">
                                <i class="fas fa-check"></i> Test Success
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button onclick="showError('เกิดข้อผิดพลาด!')" 
                                    class="action-btn d-block" style="background-color: #e53e3e; color: white;">
                                <i class="fas fa-times"></i> Test Error
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button onclick="showWarning('คำเตือน: โปรดตรวจสอบข้อมูล')" 
                                    class="action-btn d-block" style="background-color: #ff8c00; color: white;">
                                <i class="fas fa-exclamation-triangle"></i> Test Warning
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button onclick="showInfo('ข้อมูลเพิ่มเติม')" 
                                    class="action-btn d-block" style="background-color: #4299e1; color: white;">
                                <i class="fas fa-info-circle"></i> Test Info
                            </button>
                        </div>
                    </div>
                    
                    <!-- Livewire Table Demo -->
                    <h6 style="margin-bottom: 15px; color: #2d3748;">Livewire Searchable Table (Users)</h6>
                    @livewire('searchable-table', [
                        'modelClass' => \App\Models\User::class,
                        'columnConfig' => [
                            ['field' => 'id', 'label' => 'ID'],
                            ['field' => 'name', 'label' => 'ชื่อ'],
                            ['field' => 'email', 'label' => 'อีเมล'],
                            ['field' => 'created_at', 'label' => 'วันที่สร้าง', 'type' => 'datetime', 'format' => 'd/m/Y H:i']
                        ],
                        'searchableFields' => ['name', 'email']
                    ])
                </div>

                <!-- Floating Action Button -->
                <div class="fab-container">
                    <div class="fab-menu" id="fabMenu">
                        <a href="{{ route('purchase-requisitions.create-direct-small') }}" class="fab-item">
                            <i class="fas fa-shopping-cart text-success"></i>
                            <span>PR ≤10,000</span>
                        </a>
                        <a href="{{ route('purchase-requisitions.create-direct-medium') }}" class="fab-item">
                            <i class="fas fa-shopping-basket text-warning"></i>
                            <span>PR ≤100,000</span>
                        </a>
                        <a href="{{ route('purchase-orders.create') }}" class="fab-item">
                            <i class="fas fa-file-alt text-primary"></i>
                            <span>สร้าง PO</span>
                        </a>
                        <a href="{{ route('vendors.index') }}" class="fab-item">
                            <i class="fas fa-store text-info"></i>
                            <span>จัดการผู้ขาย</span>
                        </a>
                    </div>
                    <button class="fab" id="fabButton">
                        <i class="fas fa-plus" id="fabIcon"></i>
                    </button>
                </div>

                @push('scripts')
                <!-- FullCalendar CSS and JS -->
                <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
                <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let calendar = null;
                    
                    // Theme manager is handled globally - no need for local theme management
                    
                    // Floating Action Button Management
                    const fabManager = {
                        init() {
                            this.bindEvents();
                        },
                        
                        bindEvents() {
                            const fabButton = document.getElementById('fabButton');
                            const fabMenu = document.getElementById('fabMenu');
                            const fabIcon = document.getElementById('fabIcon');
                            
                            fabButton.addEventListener('click', () => {
                                const isOpen = fabMenu.classList.contains('show');
                                
                                if (isOpen) {
                                    this.closeFab();
                                } else {
                                    this.openFab();
                                }
                            });
                            
                            // Close FAB when clicking outside
                            document.addEventListener('click', (e) => {
                                if (!e.target.closest('.fab-container')) {
                                    this.closeFab();
                                }
                            });
                        },
                        
                        openFab() {
                            const fabMenu = document.getElementById('fabMenu');
                            const fabIcon = document.getElementById('fabIcon');
                            
                            fabMenu.classList.add('show');
                            fabIcon.style.transform = 'rotate(45deg)';
                        },
                        
                        closeFab() {
                            const fabMenu = document.getElementById('fabMenu');
                            const fabIcon = document.getElementById('fabIcon');
                            
                            fabMenu.classList.remove('show');
                            fabIcon.style.transform = 'rotate(0deg)';
                        }
                    };
                    
                    // Dashboard Customization Manager
                    const customizationManager = {
                        init() {
                            this.loadCustomizations();
                            this.bindEvents();
                        },
                        
                        loadCustomizations() {
                            const customizations = JSON.parse(localStorage.getItem('dashboard-customizations') || '{}');
                            
                            // Apply saved customizations
                            Object.keys(customizations).forEach(key => {
                                const checkbox = document.getElementById(key);
                                const section = this.getSectionById(key);
                                
                                if (checkbox && section) {
                                    checkbox.checked = customizations[key];
                                    section.style.display = customizations[key] ? 'block' : 'none';
                                }
                            });
                        },
                        
                        saveCustomizations() {
                            const customizations = {
                                showStats: document.getElementById('showStats').checked,
                                showCalendar: document.getElementById('showCalendar').checked,
                                showQuickActions: document.getElementById('showQuickActions').checked
                            };
                            
                            localStorage.setItem('dashboard-customizations', JSON.stringify(customizations));
                        },
                        
                        getSectionById(id) {
                            switch(id) {
                                case 'showStats':
                                    return document.querySelector('.row.g-4'); // Stats section
                                case 'showCalendar':
                                    return document.querySelector('.row.g-4.mt-4'); // Calendar section  
                                case 'showQuickActions':
                                    return document.querySelector('.quick-action-card:last-of-type'); // Quick actions section
                                default:
                                    return null;
                            }
                        },
                        
                        bindEvents() {
                            // Section visibility toggles
                            ['showStats', 'showCalendar', 'showQuickActions'].forEach(id => {
                                const checkbox = document.getElementById(id);
                                if (checkbox) {
                                    checkbox.addEventListener('change', (e) => {
                                        const section = this.getSectionById(id);
                                        if (section) {
                                            section.style.display = e.target.checked ? 'block' : 'none';
                                        }
                                        this.saveCustomizations();
                                    });
                                }
                            });
                            
                            // Calendar filters
                            ['showPO', 'showPR'].forEach(id => {
                                const checkbox = document.getElementById(id);
                                if (checkbox) {
                                    checkbox.addEventListener('change', () => {
                                        this.filterCalendarEvents();
                                        this.saveCalendarFilters();
                                    });
                                }
                            });
                            
                            // Reset dashboard
                            document.getElementById('resetDashboard').addEventListener('click', (e) => {
                                e.preventDefault();
                                this.resetDashboard();
                            });
                        },
                        
                        filterCalendarEvents() {
                            if (!calendar) return;
                            
                            const showPO = document.getElementById('showPO').checked;
                            const showPR = document.getElementById('showPR').checked;
                            
                            const filteredEvents = calendarEvents.filter(event => {
                                if (event.type === 'po_delivery' || event.type === 'po_overdue') {
                                    return showPO;
                                } else if (event.type === 'pr_required') {
                                    return showPR;
                                }
                                return true;
                            });
                            
                            calendar.removeAllEvents();
                            calendar.addEventSource(filteredEvents);
                        },
                        
                        saveCalendarFilters() {
                            const filters = {
                                showPO: document.getElementById('showPO').checked,
                                showPR: document.getElementById('showPR').checked
                            };
                            localStorage.setItem('calendar-filters', JSON.stringify(filters));
                        },
                        
                        loadCalendarFilters() {
                            const filters = JSON.parse(localStorage.getItem('calendar-filters') || '{"showPO": true, "showPR": true}');
                            
                            document.getElementById('showPO').checked = filters.showPO;
                            document.getElementById('showPR').checked = filters.showPR;
                            
                            // Apply filters after calendar is initialized
                            setTimeout(() => this.filterCalendarEvents(), 100);
                        },
                        
                        resetDashboard() {
                            if (confirm('คุณต้องการรีเซ็ตการตั้งค่าแดชบอร์ดทั้งหมดหรือไม่?')) {
                                localStorage.removeItem('dashboard-customizations');
                                localStorage.removeItem('calendar-filters');
                                localStorage.removeItem('innobic-theme');
                                location.reload();
                            }
                        }
                    };
                    
                    // Initialize managers
                    fabManager.init();
                    customizationManager.init();
                    
                    // Prepare calendar events data
                    const calendarEvents = @json($calendarEvents->values());

                    // View switcher for calendar
                    const viewButtons = document.querySelectorAll('[data-view]');
                    const listView = document.getElementById('listView');
                    const calendarView = document.getElementById('calendarView');
                    
                    viewButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const view = this.getAttribute('data-view');
                            
                            // Update active button
                            viewButtons.forEach(btn => btn.classList.remove('active'));
                            this.classList.add('active');
                            
                            // Show/hide views
                            if (view === 'list') {
                                listView.style.display = 'block';
                                calendarView.style.display = 'none';
                            } else {
                                listView.style.display = 'none';
                                calendarView.style.display = 'block';
                                // Initialize calendar if not already done
                                initCalendar();
                            }
                        });
                    });
                    
                    // Show more events
                    const showMoreBtn = document.getElementById('showMoreEvents');
                    if (showMoreBtn) {
                        showMoreBtn.addEventListener('click', function() {
                            // Here you could load more events via AJAX
                            alert('ฟีเจอร์นี้จะเพิ่มในเวอร์ชันถัดไป');
                        });
                    }
                    
                    function initCalendar() {
                        const calendarEl = document.getElementById('calendar');
                        if (calendarEl && !calendar) {
                            calendar = new FullCalendar.Calendar(calendarEl, {
                                headerToolbar: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                                },
                                initialView: 'dayGridMonth',
                                locale: 'th',
                                firstDay: 1, // Monday
                                events: calendarEvents,
                                eventDisplay: 'block',
                                height: 'auto',
                                dayMaxEvents: 3, // Limit events per day
                                moreLinkClick: 'popover',
                                
                                // Drag & Drop functionality
                                editable: true,
                                eventDrop: function(info) {
                                    handleEventDrop(info);
                                },
                                
                                // Visual feedback during drag
                                eventDragStart: function(info) {
                                    // Add visual feedback
                                    info.el.style.opacity = '0.7';
                                    document.body.style.cursor = 'grabbing';
                                },
                                eventDragStop: function(info) {
                                    // Reset visual feedback
                                    info.el.style.opacity = '1';
                                    document.body.style.cursor = 'default';
                                },
                                
                                eventClick: function(info) {
                                    // Show event details in modal or redirect
                                    if (info.event.extendedProps.url) {
                                        window.open(info.event.extendedProps.url, '_blank');
                                        info.jsEvent.preventDefault();
                                    } else {
                                        // Show modal with event details
                                        showEventModal(info.event);
                                    }
                                },
                                eventDidMount: function(info) {
                                    // Add tooltip
                                    const description = info.event.extendedProps.description || info.event.title;
                                    info.el.setAttribute('title', description + ' (ลากเพื่อเปลี่ยนวันที่)');
                                    
                                    // Add icon based on event type
                                    let icon = '';
                                    const eventType = info.event.extendedProps.type;
                                    if (eventType === 'po_delivery') {
                                        icon = '<i class="fas fa-truck me-1"></i>';
                                    } else if (eventType === 'pr_required') {
                                        icon = '<i class="fas fa-clock me-1"></i>';
                                    } else if (eventType === 'po_overdue') {
                                        icon = '<i class="fas fa-exclamation-triangle me-1"></i>';
                                    }
                                    
                                    // Prepend icon to title
                                    const titleEl = info.el.querySelector('.fc-event-title');
                                    if (titleEl && icon) {
                                        titleEl.innerHTML = icon + titleEl.textContent;
                                    }
                                    
                                    // Add drag cursor
                                    if (info.event.extendedProps.editable !== false) {
                                        info.el.style.cursor = 'grab';
                                        info.el.addEventListener('mousedown', () => {
                                            info.el.style.cursor = 'grabbing';
                                        });
                                        info.el.addEventListener('mouseup', () => {
                                            info.el.style.cursor = 'grab';
                                        });
                                    }
                                },
                                dayCellClassNames: function(info) {
                                    // Highlight today
                                    if (info.isToday) {
                                        return ['today-highlight'];
                                    }
                                    return [];
                                }
                            });
                            
                            calendar.render();
                            
                            // Load calendar filters after calendar is rendered
                            customizationManager.loadCalendarFilters();
                        }
                    }
                    
                    function showEventModal(event) {
                        // Simple alert for now - you can create a proper modal
                        let priorityText = {
                            'urgent': 'ด่วนมาก',
                            'high': 'ด่วน', 
                            'medium': 'ปกติ',
                            'low': 'ต่ำ'
                        };
                        
                        alert(`${event.title}\n\n${event.extendedProps.description}\n\nระดับความสำคัญ: ${priorityText[event.extendedProps.priority]}\nวันที่: ${event.startStr}`);
                    }
                    
                    // Handle drag & drop event updates
                    function handleEventDrop(info) {
                        const event = info.event;
                        const newDate = event.startStr;
                        const entityId = event.extendedProps.entity_id;
                        const entityType = event.extendedProps.entity_type;
                        
                        // Show loading state
                        event.setProp('title', event.title + ' (กำลังอัปเดต...)');
                        event.el.style.opacity = '0.6';
                        
                        // Send AJAX request to update date
                        fetch('{{ route("dashboard.update-event-date") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: entityId,
                                type: entityType,
                                new_date: newDate
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Success notification
                                showNotification('success', data.message);
                                
                                // Reset title and opacity
                                event.setProp('title', event.title.replace(' (กำลังอัปเดต...)', ''));
                                event.el.style.opacity = '1';
                                
                                // Update event with new data (optional: refetch colors based on new priority)
                                updateEventAfterDrop(event, newDate);
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาดในการอัปเดต');
                            }
                        })
                        .catch(error => {
                            // Error notification
                            showNotification('error', error.message || 'เกิดข้อผิดพลาดในการอัปเดต');
                            
                            // Revert the event to original position
                            info.revert();
                        });
                    }
                    
                    // Update event appearance after successful drop
                    function updateEventAfterDrop(event, newDate) {
                        // Recalculate priority based on new date
                        const today = new Date();
                        const eventDate = new Date(newDate);
                        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
                        
                        let newPriority = 'normal';
                        let newColor = '#38a169'; // Default green
                        
                        if (daysUntil < 0) {
                            newPriority = 'overdue';
                            newColor = '#dc2626'; // Red
                        } else if (daysUntil <= 3) {
                            if (event.extendedProps.entity_type === 'po') {
                                newPriority = 'po_urgent';
                                newColor = '#1e3a8a'; // Dark blue
                            } else {
                                newPriority = 'pr_urgent';
                                newColor = '#166534'; // Dark green
                            }
                        } else if (daysUntil <= 7) {
                            if (event.extendedProps.entity_type === 'po') {
                                newPriority = 'po_high';
                                newColor = '#3b82f6'; // Medium blue
                            } else {
                                newPriority = 'pr_high';
                                newColor = '#22c55e'; // Medium green
                            }
                        } else {
                            if (event.extendedProps.entity_type === 'po') {
                                newPriority = 'po_normal';
                                newColor = '#93c5fd'; // Light blue
                            } else {
                                newPriority = 'pr_normal';
                                newColor = '#86efac'; // Light green
                            }
                        }
                        
                        // Update event colors
                        event.setProp('backgroundColor', newColor);
                        event.setProp('borderColor', newColor);
                        event.setExtendedProp('priority', newPriority);
                    }
                    
                    // Notification system for drag & drop feedback
                    function showNotification(type, message) {
                        // Create notification element
                        const notification = document.createElement('div');
                        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
                        notification.style.cssText = `
                            top: 20px;
                            right: 20px;
                            z-index: 9999;
                            min-width: 300px;
                            max-width: 400px;
                        `;
                        
                        notification.innerHTML = `
                            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        
                        document.body.appendChild(notification);
                        
                        // Auto remove after 4 seconds
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.remove();
                            }
                        }, 4000);
                    }
                    
                    // Initialize calendar by default on page load
                    initCalendar();
                    
                    // Auto-refresh calendar data every 5 minutes
                    setInterval(function() {
                        // Reload calendar events
                        if (calendar) {
                            fetch(window.location.href)
                                .then(response => response.text())
                                .then(html => {
                                    // Extract new events from response and update calendar
                                    console.log('Calendar data refreshed');
                                })
                                .catch(err => console.log('Calendar refresh failed:', err));
                        }
                    }, 300000); // 5 minutes
                });
                </script>

                <style>
                /* FullCalendar custom styling */
                .fc {
                    font-family: 'Sarabun', sans-serif;
                }

                .fc-event {
                    font-size: 0.85rem;
                    border-radius: 4px;
                    padding: 2px 4px;
                    margin: 1px 0;
                }

                .fc-event-title {
                    font-weight: 500;
                }

                .fc-daygrid-event {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .fc-header-toolbar {
                    margin-bottom: 1rem;
                }

                .fc-button {
                    background-color: var(--primary) !important;
                    border-color: var(--primary) !important;
                    font-size: 0.875rem;
                }

                .fc-button:hover {
                    background-color: var(--primary-dark) !important;
                    border-color: var(--primary-dark) !important;
                }

                .fc-today-button {
                    background-color: #6c757d !important;
                    border-color: #6c757d !important;
                }

                .today-highlight {
                    background-color: rgba(15, 109, 183, 0.1) !important;
                }

                .fc-day-today .fc-daygrid-day-number {
                    background-color: var(--primary);
                    color: white;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 2px;
                }

                /* Priority-based event styling */
                .priority-urgent {
                    background-color: #e53e3e !important;
                    border-color: #e53e3e !important;
                    color: white !important;
                }

                .priority-high {
                    background-color: #ff8c00 !important;
                    border-color: #ff8c00 !important;
                    color: white !important;
                }

                .priority-medium {
                    background-color: #4299e1 !important;
                    border-color: #4299e1 !important;
                    color: white !important;
                }

                .priority-low {
                    background-color: #38a169 !important;
                    border-color: #38a169 !important;
                    color: white !important;
                }

                /* Calendar Legend Styling */
                .calendar-legend {
                    margin-bottom: 1rem;
                }

                .legend-container {
                    background-color: #f8f9fa !important;
                    border: 1px solid #e9ecef;
                    border-radius: 8px;
                }

                .legend-title {
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 0.75rem;
                    font-size: 1rem;
                }

                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .legend-color {
                    width: 16px;
                    height: 16px;
                    border-radius: 3px;
                    display: inline-block;
                    border: 1px solid rgba(0,0,0,0.1);
                }

                /* PO Colors (Blue tones) */
                .legend-color.bg-po-urgent {
                    background-color: #1e3a8a;
                }

                .legend-color.bg-po-high {
                    background-color: #3b82f6;
                }

                .legend-color.bg-po-normal {
                    background-color: #93c5fd;
                }

                /* PR Colors (Green tones) */
                .legend-color.bg-pr-urgent {
                    background-color: #166534;
                }

                .legend-color.bg-pr-high {
                    background-color: #22c55e;
                }

                .legend-color.bg-pr-normal {
                    background-color: #86efac;
                }

                /* Overdue (Red) */
                .legend-color.bg-overdue {
                    background-color: #dc2626;
                }

                /* Legacy colors (fallback) */
                .legend-color.bg-urgent {
                    background-color: #e53e3e;
                }

                .legend-color.bg-high {
                    background-color: #ff8c00;
                }

                .legend-color.bg-medium {
                    background-color: #4299e1;
                }

                .legend-color.bg-low {
                    background-color: #38a169;
                }

                .legend-text {
                    font-size: 0.875rem;
                    color: #6c757d;
                    font-weight: 500;
                }

                .legend-icons {
                    border-top: 1px solid #dee2e6;
                    padding-top: 0.75rem;
                }

                .legend-icon-item {
                    display: inline-flex;
                    align-items: center;
                    white-space: nowrap;
                }

                .legend-icon-item small {
                    font-size: 0.875rem;
                    color: #6c757d;
                    font-weight: 500;
                }

                /* Responsive calendar */
                @media (max-width: 768px) {
                    .fc-header-toolbar {
                        flex-direction: column;
                        gap: 0.5rem;
                    }
                    
                    .fc-toolbar-chunk {
                        display: flex;
                        justify-content: center;
                    }
                    
                    .fc-button {
                        padding: 0.25rem 0.5rem;
                        font-size: 0.75rem;
                    }
                    
                    .fc-event {
                        font-size: 0.75rem;
                        padding: 1px 2px;
                    }

                    .legend-icon-item {
                        display: block;
                        margin-bottom: 0.25rem;
                    }

                    .legend-icons {
                        text-align: center;
                    }
                }
                </style>
                @endpush
@endsection