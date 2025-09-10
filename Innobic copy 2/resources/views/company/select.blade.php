@extends('layouts.company')

@section('title', 'เลือกบริษัท')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-5 col-lg-6 col-md-8">
        @if (session('success'))
            <div class="alert alert-success mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if ($companies->count() > 0)
            <form action="{{ route('company.set') }}" method="POST">
                @csrf
                <div class="d-grid gap-3 mb-4">
                    @foreach ($companies as $company)
                        <div class="company-option">
                            <input type="radio" 
                                   class="btn-check" 
                                   name="company_id" 
                                   id="company_{{ $company->id }}" 
                                   value="{{ $company->id }}"
                                   @if($currentCompany && $currentCompany->id == $company->id) checked @endif
                                   required>
                            <label class="company-card w-100 p-4 text-start d-block" 
                                   for="company_{{ $company->id }}">
                                <div class="d-flex align-items-center">
                                    <div class="company-logo me-3">
                                        <img src="{{ $company->getLogoUrl() }}" 
                                             alt="{{ $company->display_name }}">
                                    </div>
                                    <div class="company-info flex-grow-1">
                                        <div class="company-name">{{ $company->display_name }}</div>
                                        <div class="company-description">{{ $company->name }}</div>
                                        @if ($company->description)
                                            <div class="company-description">{{ $company->description }}</div>
                                        @endif
                                    </div>
                                    <div class="company-status">
                                        @if($currentCompany && $currentCompany->id == $company->id)
                                            <span class="status-badge">
                                                <i class="fas fa-check"></i>
                                                ปัจจุบัน
                                            </span>
                                        @endif
                                        <i class="fas fa-chevron-right ms-2" style="color: var(--text-secondary);"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="d-grid">
                    <button type="submit" class="primary-button w-100 justify-content-center">
                        <i class="fas fa-arrow-right"></i>
                        เข้าใช้งาน
                    </button>
                </div>
            </form>
        @else
            <div class="no-companies">
                <i class="fas fa-building"></i>
                <div class="h5">ไม่พบบริษัทที่สามารถเข้าใช้งานได้</div>
                <p>กรุณาติดต่อผู้ดูแลระบบเพื่อขออนุญาตเข้าใช้งาน</p>
            </div>
        @endif

        @if($currentCompany)
            <div class="text-center mt-4">
                <form action="{{ route('company.clear') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="secondary-button">
                        <i class="fas fa-sign-out-alt"></i>
                        ออกจากบริษัทปัจจุบัน
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection