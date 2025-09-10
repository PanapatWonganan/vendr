@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-lg-flex">
                        <div>
                            <h5 class="mb-0">รายละเอียดผู้ขาย</h5>
                            <p class="text-sm mb-0">
                                ข้อมูลทั้งหมดของผู้ขาย {{ $vendor->company_name }}
                            </p>
                        </div>
                        <div class="ms-auto my-auto mt-lg-0 mt-4">
                            <div class="ms-auto my-auto d-flex">
                                <a href="{{ route('vendors.index') }}" class="btn bg-gradient-secondary btn-sm mb-0 me-2">
                                    <i class="fas fa-arrow-left"></i>&nbsp;&nbsp;กลับ
                                </a>
                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn bg-gradient-primary btn-sm mb-0">
                                    <i class="fas fa-edit"></i>&nbsp;&nbsp;แก้ไข
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status Badge -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-lg {{ $vendor->status_badge_class }} text-white px-3 py-2">
                                    <i class="{{ $vendor->status_icon }} me-2"></i>
                                    {{ $vendor->status_label }}
                                </span>
                                <div class="d-flex">
                                    @if ($vendor->isPending())
                                        <button type="button" class="btn btn-success btn-sm me-2" onclick="approveVendor({{ $vendor->id }})">
                                            <i class="fas fa-check"></i> อนุมัติ
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectVendor({{ $vendor->id }})">
                                            <i class="fas fa-times"></i> ปฏิเสธ
                                        </button>
                                    @elseif ($vendor->isApproved())
                                        <button type="button" class="btn btn-warning btn-sm" onclick="suspendVendor({{ $vendor->id }})">
                                            <i class="fas fa-pause"></i> ระงับ
                                        </button>
                                    @elseif ($vendor->isSuspended())
                                        <button type="button" class="btn btn-success btn-sm" onclick="approveVendor({{ $vendor->id }})">
                                            <i class="fas fa-play"></i> เปิดใช้งาน
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ข้อมูลทั่วไป -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">ข้อมูลทั่วไป</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">ชื่อบริษัท</label>
                                <div class="form-control-static">{{ $vendor->company_name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">เลขที่ Tax ID</label>
                                <div class="form-control-static">{{ $vendor->tax_id }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">ประเภทของงาน</label>
                                <div class="form-control-static">{{ $vendor->work_category }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">วันที่ลงทะเบียน</label>
                                <div class="form-control-static">{{ $vendor->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-control-label">ที่อยู่ตามหนังสือรับรอง</label>
                                <div class="form-control-static">{{ $vendor->address }}</div>
                            </div>
                        </div>
                    </div>
                    
                    @if ($vendor->experience)
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-control-label">ประสบการณ์การทำงาน</label>
                                <div class="form-control-static">
                                    <div class="bg-light p-3 rounded">
                                        {!! nl2br(e($vendor->experience)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <hr class="horizontal dark">
                    
                    <!-- ข้อมูลผู้ติดต่อ -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">ข้อมูลผู้ติดต่อประสานงาน</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">ชื่อผู้ติดต่อ</label>
                                <div class="form-control-static">{{ $vendor->contact_name }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">เบอร์โทรศัพท์</label>
                                <div class="form-control-static">
                                    <a href="tel:{{ $vendor->contact_phone }}">{{ $vendor->contact_phone }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">อีเมล</label>
                                <div class="form-control-static">
                                    <a href="mailto:{{ $vendor->contact_email }}">{{ $vendor->contact_email }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if ($vendor->documents && count($vendor->documents) > 0)
                    <hr class="horizontal dark">
                    
                    <!-- เอกสารแนบ -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">เอกสารแนบ</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ชื่อไฟล์</th>
                                            <th>ขนาด</th>
                                            <th>ประเภท</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($vendor->documents as $index => $document)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file me-2"></i>
                                                {{ $document['name'] }}
                                            </td>
                                            <td>{{ number_format($document['size'] / 1024 / 1024, 2) }} MB</td>
                                            <td>{{ strtoupper(pathinfo($document['name'], PATHINFO_EXTENSION)) }}</td>
                                            <td>
                                                <a href="{{ asset('storage/' . $document['path']) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank">
                                                    <i class="fas fa-download"></i> ดาวน์โหลด
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <hr class="horizontal dark">
                    
                    <!-- ข้อมูลระบบ -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">ข้อมูลระบบ</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">วันที่สร้าง</label>
                                <div class="form-control-static">{{ $vendor->created_at->format('d/m/Y H:i:s') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">วันที่แก้ไขล่าสุด</label>
                                <div class="form-control-static">{{ $vendor->updated_at->format('d/m/Y H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Forms -->
<form id="approve-form" method="POST" style="display: none;">
    @csrf
</form>

<form id="reject-form" method="POST" style="display: none;">
    @csrf
</form>

<form id="suspend-form" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@push('styles')
<style>
.form-control-static {
    padding: 0.375rem 0.75rem;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    min-height: 38px;
    display: flex;
    align-items: center;
}

.badge-lg {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}
</style>
@endpush

@push('scripts')
<script>
function approveVendor(vendorId) {
    if (confirm('คุณต้องการอนุมัติผู้ขายนี้หรือไม่?')) {
        const form = document.getElementById('approve-form');
        form.action = `/vendors/${vendorId}/approve`;
        form.submit();
    }
}

function rejectVendor(vendorId) {
    if (confirm('คุณต้องการปฏิเสธผู้ขายนี้หรือไม่?')) {
        const form = document.getElementById('reject-form');
        form.action = `/vendors/${vendorId}/reject`;
        form.submit();
    }
}

function suspendVendor(vendorId) {
    if (confirm('คุณต้องการระงับผู้ขายนี้หรือไม่?')) {
        const form = document.getElementById('suspend-form');
        form.action = `/vendors/${vendorId}/suspend`;
        form.submit();
    }
}
</script>
@endpush 