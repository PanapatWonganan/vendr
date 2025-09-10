@extends('layouts.company')

@section('title', 'เลือกบริษัท')

@section('content')
<div class="company-select-container">
    @if (session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    @if ($companies->count() > 0)
        <form action="{{ route('company.set') }}" method="POST" class="company-form">
            @csrf
            <div class="companies-grid">
                @foreach ($companies as $company)
                    <div class="company-item">
                        <input type="radio" 
                               id="company_{{ $company->id }}" 
                               name="company_id" 
                               value="{{ $company->id }}"
                               @if($currentCompany && $currentCompany->id == $company->id) checked @endif
                               required>
                        <label for="company_{{ $company->id }}" class="company-card">
                            <div class="company-logo">
                                <img src="{{ $company->getLogoUrl() }}" alt="{{ $company->display_name }}">
                            </div>
                            <div class="company-info">
                                <h3 class="company-name">{{ $company->display_name }}</h3>
                                @if ($company->description)
                                    <p class="company-desc">{{ $company->description }}</p>
                                @endif
                            </div>
                            @if($currentCompany && $currentCompany->id == $company->id)
                                <div class="current-badge">
                                    <i class="fas fa-check"></i>
                                    <span>ปัจจุบัน</span>
                                </div>
                            @endif
                            <div class="select-indicator">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i>
                <span>เข้าใช้งาน</span>
            </button>
        </form>
    @else
        <div class="no-companies">
            <i class="fas fa-building"></i>
            <h3>ไม่พบบริษัท</h3>
            <p>กรุณาติดต่อผู้ดูแลระบบเพื่อขออนุญาตเข้าใช้งาน</p>
        </div>
    @endif

    @if($currentCompany)
        <div class="logout-section">
            <form action="{{ route('company.clear') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>ออกจากบริษัทปัจจุบัน</span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection