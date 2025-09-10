@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-lg-flex">
                        <div>
                            <h5 class="mb-0">รายชื่อผู้ขาย</h5>
                            <p class="text-sm mb-0">
                                จัดการข้อมูลผู้ขายและการลงทะเบียน
                            </p>
                        </div>
                        <div class="ms-auto my-auto mt-lg-0 mt-4">
                            <div class="ms-auto my-auto">
                                <a href="{{ route('vendors.create') }}" class="btn bg-gradient-primary btn-sm mb-0">
                                    <i class="fas fa-plus"></i>&nbsp;&nbsp;เพิ่มผู้ขาย
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-0">
                    <div class="table-responsive">
                        <table class="table table-flush" id="vendors-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ชื่อบริษัท</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tax ID</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ประเภทงาน</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ผู้ติดต่อ</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">สถานะ</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">วันที่สร้าง</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vendors as $vendor)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $vendor->company_name }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ Str::limit($vendor->address, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">{{ $vendor->tax_id }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">
                                            {{ $vendor->work_category }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-secondary text-xs font-weight-bold">{{ $vendor->contact_name }}</span>
                                            <span class="text-secondary text-xs">{{ $vendor->contact_phone }}</span>
                                            <span class="text-secondary text-xs">{{ $vendor->contact_email }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-lg {{ $vendor->status_badge_class }} text-white px-3 py-2">
                                            <i class="{{ $vendor->status_icon }} me-1"></i>
                                            {{ $vendor->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">{{ $vendor->created_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- ปุ่มดูรายละเอียด -->
                                            <a href="{{ route('vendors.show', $vendor) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- ปุ่มแก้ไข -->
                                            <a href="{{ route('vendors.edit', $vendor) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Dropdown สำหรับ actions อื่นๆ -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown" 
                                                        aria-expanded="false"
                                                        title="การดำเนินการเพิ่มเติม">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($vendor->isPending())
                                                    <li>
                                                        <a class="dropdown-item text-success" href="#" onclick="approveVendor({{ $vendor->id }})">
                                                            <i class="fas fa-check me-2"></i>อนุมัติ
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="rejectVendor({{ $vendor->id }})">
                                                            <i class="fas fa-times me-2"></i>ปฏิเสธ
                                                        </a>
                                                    </li>
                                                    @elseif ($vendor->isApproved())
                                                    <li>
                                                        <a class="dropdown-item text-warning" href="#" onclick="suspendVendor({{ $vendor->id }})">
                                                            <i class="fas fa-pause me-2"></i>ระงับ
                                                        </a>
                                                    </li>
                                                    @elseif ($vendor->isSuspended())
                                                    <li>
                                                        <a class="dropdown-item text-success" href="#" onclick="approveVendor({{ $vendor->id }})">
                                                            <i class="fas fa-play me-2"></i>เปิดใช้งาน
                                                        </a>
                                                    </li>
                                                    @endif
                                                    @if (!$vendor->isPending() || $vendor->isRejected())
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="deleteVendor({{ $vendor->id }})">
                                                            <i class="fas fa-trash me-2"></i>ลบ
                                                        </a>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                                            <h6 class="text-secondary">ไม่พบข้อมูลผู้ขาย</h6>
                                            <p class="text-secondary mb-0">เริ่มต้นโดยการเพิ่มผู้ขายใหม่</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if ($vendors->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $vendors->links() }}
                    </div>
                    @endif
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

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

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

function deleteVendor(vendorId) {
    if (confirm('คุณต้องการลบผู้ขายนี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.getElementById('delete-form');
        form.action = `/vendors/${vendorId}`;
        form.submit();
    }
}
</script>
@endpush 