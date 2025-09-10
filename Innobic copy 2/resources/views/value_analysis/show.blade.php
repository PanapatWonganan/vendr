@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('รายละเอียด Value Analysis') }} - {{ $valueAnalysis->va_number }}
    </h2>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">รายละเอียด Value Analysis - {{ $valueAnalysis->va_number }}</h5>
                    <div>
                        <a href="{{ route('value-analysis.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> กลับ
                        </a>
                        @if($valueAnalysis->canEdit())
                            <a href="{{ route('value-analysis.edit', $valueAnalysis) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <!-- สถานะและข้อมูลหลัก -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-muted mb-3">ข้อมูลพื้นฐาน</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">เลขที่ VA:</label>
                                    <div>{{ $valueAnalysis->va_number }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">สถานะ:</label>
                                    <div>
                                        <span class="badge {{ $valueAnalysis->status_badge }} fs-6">
                                            {{ $valueAnalysis->status_text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">ประเภทงาน:</label>
                                    <div>
                                        <span class="badge bg-info">{{ $valueAnalysis->work_type_label }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">วิธีจัดหา:</label>
                                    <div>
                                        @if($valueAnalysis->procurement_method)
                                            <span class="badge bg-secondary">{{ $valueAnalysis->procurement_method_label }}</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">จัดหาจาก:</label>
                                    <div>
                                        @if($valueAnalysis->procured_from)
                                            {!! nl2br(e($valueAnalysis->procured_from)) !!}
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">วงเงินที่ตกลงซื้อหรือจ้าง:</label>
                                    <div>
                                        @if($valueAnalysis->agreed_amount)
                                            {{ number_format($valueAnalysis->agreed_amount, 2) }} บาท
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">งบประมาณ:</label>
                                    <div class="fw-bold text-primary fs-5">
                                        {{ number_format($valueAnalysis->total_budget, 2) }} {{ $valueAnalysis->currency }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">วันที่สร้าง:</label>
                                    <div>{{ $valueAnalysis->created_at?->format('d/m/Y H:i น.') ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted mb-3">การดำเนินการ</h6>
                            <div class="d-grid gap-2">
                                @if($valueAnalysis->status === 'draft')
                                    <form action="{{ route('value-analysis.start-analysis', $valueAnalysis) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-play"></i> เริ่มการวิเคราะห์
                                        </button>
                                    </form>
                                @endif

                                @if($valueAnalysis->status === 'in_progress')
                                    <form action="{{ route('value-analysis.complete-analysis', $valueAnalysis) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-info w-100">
                                            <i class="fas fa-check"></i> ทำเครื่องหมายเสร็จสิ้น
                                        </button>
                                    </form>
                                @endif

                                @if($valueAnalysis->canApprove())
                                    <form action="{{ route('value-analysis.approve', $valueAnalysis) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการอนุมัติ Value Analysis นี้?')">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-thumbs-up"></i> อนุมัติ
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('value-analysis.reject', $valueAnalysis) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการปฏิเสธ Value Analysis นี้?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="fas fa-thumbs-down"></i> ปฏิเสธ
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูล PR ที่เกี่ยวข้อง -->
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-file-invoice me-2"></i>
                                ข้อมูล Purchase Requisition ที่เกี่ยวข้อง
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">เลขที่ PR:</label>
                                    <div>
                                        @if($valueAnalysis->purchaseRequisition)
                                            <a href="{{ route('purchase-requisitions.show', $valueAnalysis->purchaseRequisition->id) }}" 
                                               class="text-decoration-none">
                                                {{ $valueAnalysis->purchaseRequisition->pr_number }}
                                                <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">หัวข้อ PR:</label>
                                    <div>{{ $valueAnalysis->purchaseRequisition->title ?? '-' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">แผนก:</label>
                                    <div>{{ $valueAnalysis->purchaseRequisition->department->name ?? '-' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">ผู้ขอ:</label>
                                    <div>{{ $valueAnalysis->purchaseRequisition->requester->name ?? '-' }}</div>
                                </div>
                                @if($valueAnalysis->purchaseRequisition && $valueAnalysis->purchaseRequisition->description)
                                <div class="col-12">
                                    <label class="form-label fw-bold">รายละเอียด PR:</label>
                                    <div class="bg-white p-3 rounded border">
                                        {{ $valueAnalysis->purchaseRequisition->description }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- วัตถุประสงค์และขอบเขต -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">วัตถุประสงค์การวิเคราะห์</h6>
                                </div>
                                <div class="card-body">
                                    @if($valueAnalysis->analysis_objective)
                                        <p class="mb-0">{{ $valueAnalysis->analysis_objective }}</p>
                                    @else
                                        <p class="text-muted mb-0">ยังไม่ได้ระบุ</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">ขอบเขตการวิเคราะห์</h6>
                                </div>
                                <div class="card-body">
                                    @if($valueAnalysis->analysis_scope)
                                        <p class="mb-0">{{ $valueAnalysis->analysis_scope }}</p>
                                    @else
                                        <p class="text-muted mb-0">ยังไม่ได้ระบุ</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ผลการวิเคราะห์ -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">ข้อเสนอแนะ</h6>
                                </div>
                                <div class="card-body">
                                    @if($valueAnalysis->recommendations)
                                        <p class="mb-0">{{ $valueAnalysis->recommendations }}</p>
                                    @else
                                        <p class="text-muted mb-0">ยังไม่ได้ระบุ</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">สรุปผล</h6>
                                </div>
                                <div class="card-body">
                                    @if($valueAnalysis->conclusion)
                                        <p class="mb-0">{{ $valueAnalysis->conclusion }}</p>
                                    @else
                                        <p class="text-muted mb-0">ยังไม่ได้ระบุ</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลการต่อรองราคา -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-handshake text-info me-2"></i>
                                ข้อมูลการต่อรองราคา
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">วงเงินในการจัดหา:</label>
                                    <div class="h5">
                                        @if($valueAnalysis->total_budget)
                                            <span class="text-primary">{{ number_format($valueAnalysis->total_budget, 2) }} บาท</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">วงเงินที่ตกลงซื้อจ้าง:</label>
                                    <div class="h5">
                                        @if($valueAnalysis->agreed_amount)
                                            <span class="text-warning">{{ number_format($valueAnalysis->agreed_amount, 2) }} บาท</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">ส่วนต่าง:</label>
                                    <div class="h5">
                                        @if($valueAnalysis->price_difference !== null)
                                            <span class="{{ $valueAnalysis->negotiation_status_class }}">
                                                {{ number_format($valueAnalysis->price_difference, 2) }} บาท
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">ผลการต่อรองราคา:</label>
                                    <div class="h5">
                                        <span class="{{ $valueAnalysis->negotiation_status_class }} fw-bold">
                                            {{ $valueAnalysis->negotiation_result }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            @if($valueAnalysis->price_difference_percentage !== null)
                            <div class="row">
                                <div class="col-12">
                                    <div class="progress" style="height: 25px;">
                                        @php
                                            $percentage = $valueAnalysis->price_difference_percentage;
                                            $barWidth = min(abs($percentage), 100);
                                            $barClass = $percentage > 0 ? 'bg-success' : ($percentage < 0 ? 'bg-danger' : 'bg-info');
                                        @endphp
                                        <div class="progress-bar {{ $barClass }}" 
                                             role="progressbar" 
                                             style="width: {{ $barWidth }}%" 
                                             aria-valuenow="{{ $barWidth }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            @if($percentage > 0)
                                                ประหยัด {{ number_format($percentage, 2) }}%
                                            @elseif($percentage < 0)
                                                เกิน {{ number_format(abs($percentage), 2) }}%
                                            @else
                                                ตรงงบ 0%
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($valueAnalysis->total_budget && $valueAnalysis->agreed_amount)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert {{ $valueAnalysis->price_difference_percentage > 0 ? 'alert-success' : ($valueAnalysis->price_difference_percentage < 0 ? 'alert-danger' : 'alert-info') }}">
                                        <i class="fas fa-info-circle me-2"></i>
                                        @if($valueAnalysis->price_difference_percentage > 0)
                                            <strong>ผลการต่อรองราคาดี:</strong> สามารถประหยัดงบประมาณได้ {{ number_format($valueAnalysis->price_difference, 2) }} บาท
                                        @elseif($valueAnalysis->price_difference_percentage < 0)
                                            <strong>เกินงบประมาณ:</strong> เกินจากงบประมาณที่กำหนดไว้ {{ number_format(abs($valueAnalysis->price_difference), 2) }} บาท
                                        @else
                                            <strong>ตรงตามงบประมาณ:</strong> ราคาที่ตกลงตรงกับงบประมาณที่กำหนดไว้
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- ข้อมูลผู้ใช้งาน -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">ข้อมูลผู้ดำเนินการ</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ผู้สร้าง:</label>
                                    <div>{{ $valueAnalysis->creator->name ?? 'ไม่ระบุ' }}</div>
                                    <small class="text-muted">{{ $valueAnalysis->created_at?->format('d/m/Y H:i น.') ?? '-' }}</small>
                                </div>
                                @if($valueAnalysis->analyzer)
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ผู้วิเคราะห์:</label>
                                    <div>{{ $valueAnalysis->analyzer->name ?? 'ไม่ระบุ' }}</div>
                                    @if($valueAnalysis->analysis_date)
                                        <small class="text-muted">{{ $valueAnalysis->analysis_date?->format('d/m/Y H:i น.') ?? '-' }}</small>
                                    @endif
                                </div>
                                @endif
                                @if($valueAnalysis->approver)
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ผู้อนุมัติ:</label>
                                    <div>{{ $valueAnalysis->approver->name ?? 'ไม่ระบุ' }}</div>
                                    @if($valueAnalysis->approved_at)
                                        <small class="text-muted">{{ $valueAnalysis->approved_at?->format('d/m/Y H:i น.') ?? '-' }}</small>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 