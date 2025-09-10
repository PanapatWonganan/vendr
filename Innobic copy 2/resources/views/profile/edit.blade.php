@extends('layouts.app')

@section('title', 'โปรไฟล์')

@section('content')
<h4 class="page-title">ข้อมูลโปรไฟล์</h4>
<p class="text-muted mb-4">จัดการข้อมูลบัญชีและการตั้งค่าความปลอดภัยของคุณ</p>

<div class="row">
    <div class="col-lg-4 order-lg-2 mb-4">
        <div class="content-card mb-4">
            <div class="card-header bg-primary bg-opacity-10">
                <h5 class="text-primary mb-0">สถานะบัญชี</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <h5 class="mb-1">{{ auth()->user()->name }}</h5>
                    <p class="text-muted mb-0">{{ auth()->user()->email }}</p>
                </div>

                <div class="mb-3 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">แผนก</span>
                        <span class="fw-semibold">{{ auth()->user()->department ? auth()->user()->department->name : 'ไม่ระบุ' }}</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">บทบาท</span>
                        <span></span>
                    </div>
                    <div>
                        @forelse(auth()->user()->roles as $role)
                            <span class="badge bg-primary mb-1 me-1 p-2">{{ $role->display_name ?? $role->name }}</span>
                        @empty
                            <span class="badge bg-secondary mb-1 me-1">ไม่มีบทบาท</span>
                        @endforelse
                    </div>
                </div>

                <div class="mb-3 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">วันที่ลงทะเบียน</span>
                        <span class="fw-semibold">{{ auth()->user()->created_at->format('d M Y') }}</span>
                    </div>
                </div>

                @if(auth()->user()->email_verified_at)
                <div class="mb-3 p-3 bg-success bg-opacity-10 rounded">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <div>
                            <span class="fw-semibold">ยืนยันอีเมลแล้ว</span>
                            <span class="d-block text-muted small">{{ auth()->user()->email_verified_at->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                </div>
                @else
                <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <span class="fw-semibold">ยังไม่ได้ยืนยันอีเมล</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="content-card">
            <div class="card-header bg-danger bg-opacity-10">
                <h5 class="text-danger mb-0">ลบบัญชี</h5>
            </div>
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    <div class="col-lg-8 order-lg-1">
        <div class="content-card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">ข้อมูลส่วนตัว</h5>
            </div>
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">ความปลอดภัย</h5>
            </div>
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .user-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary);
        color: white;
        border-radius: 50%;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    /* Fix for overlapping text */
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
    }
    
    .input-group {
        position: relative;
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    /* Additional styles for better spacing */
    section > header {
        margin-bottom: 1.5rem;
    }
    
    section header h2 {
        margin-bottom: 0.25rem !important;
    }
    
    .alert {
        margin-bottom: 1rem;
    }
</style>
@endpush
