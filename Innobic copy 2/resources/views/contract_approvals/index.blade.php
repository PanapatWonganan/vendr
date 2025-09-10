@extends('layouts.app')

@section('title', 'รายการสัญญาที่ต้องอนุมัติ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-contract me-2"></i>รายการสัญญาที่ต้องอนุมัติ
                    </h6>
                    @if(auth()->user()->roles->contains('name', 'admin'))
                    <a href="{{ route('contract-approvals.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> อัพโหลดสัญญาใหม่
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <!-- ฟิลเตอร์ -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <form action="{{ route('contract-approvals.index') }}" method="GET" id="filter-form">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="status" class="form-label">สถานะ</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">ทั้งหมด</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                                            <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>กำลังตรวจสอบ</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติ</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ไม่อนุมัติ</option>
                                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contract_type" class="form-label">ประเภทสัญญา</label>
                                        <select class="form-select" id="contract_type" name="contract_type">
                                            <option value="">ทั้งหมด</option>
                                            <option value="purchase" {{ request('contract_type') == 'purchase' ? 'selected' : '' }}>จัดซื้อ</option>
                                            <option value="service" {{ request('contract_type') == 'service' ? 'selected' : '' }}>จัดจ้าง</option>
                                            <option value="rental" {{ request('contract_type') == 'rental' ? 'selected' : '' }}>เช่า</option>
                                            <option value="maintenance" {{ request('contract_type') == 'maintenance' ? 'selected' : '' }}>บำรุงรักษา</option>
                                            <option value="other" {{ request('contract_type') == 'other' ? 'selected' : '' }}>อื่นๆ</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="priority" class="form-label">ความสำคัญ</label>
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="">ทั้งหมด</option>
                                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                            <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="query" class="form-label">ค้นหา</label>
                                        <input type="text" class="form-control" id="query" name="query" 
                                               value="{{ request('query') }}" 
                                               placeholder="เลขที่สัญญา, ชื่อสัญญา, ผู้ขาย">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> ค้นหา
                                        </button>
                                        <a href="{{ route('contract-approvals.index') }}" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-times me-1"></i> ล้างตัวกรอง
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ตารางรายการสัญญา -->
                    @if($contracts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>เลขที่สัญญา</th>
                                    <th>ชื่อสัญญา</th>
                                    <th>ผู้ขาย/ผู้รับจ้าง</th>
                                    <th>ประเภท</th>
                                    <th>มูลค่า</th>
                                    <th>ความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th>วันที่อัพโหลด</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contracts as $contract)
                                <tr>
                                    <td>
                                        <strong>{{ $contract->contract_number }}</strong>
                                    </td>
                                    <td>
                                        <div>{{ $contract->contract_title }}</div>
                                        @if($contract->description)
                                        <small class="text-muted">{{ Str::limit($contract->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $contract->vendor_name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $contract->contract_type_text }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($contract->contract_value, 2) }}</strong>
                                        <small class="text-muted">{{ $contract->currency }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $contract->priority_badge }}">{{ $contract->priority_text }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $contract->status_badge }}">{{ $contract->status_text }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $contract->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $contract->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('contract-approvals.show', $contract) }}" 
                                               class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(in_array($contract->status, ['pending', 'rejected']))
                                            <a href="{{ route('contract-approvals.edit', $contract) }}" 
                                               class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-contract-id="{{ $contract->id }}"
                                                    data-contract-number="{{ $contract->contract_number }}"
                                                    title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $contracts->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                        <h5>ไม่พบข้อมูลสัญญา</h5>
                        <p class="text-muted">ยังไม่มีสัญญาในระบบหรือข้อมูลไม่ตรงกับเงื่อนไขการค้นหา</p>
                        @if(auth()->user()->roles->contains('name', 'admin'))
                        <a href="{{ route('contract-approvals.create') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-plus me-1"></i> อัพโหลดสัญญาใหม่
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> ยืนยันการลบสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i> การลบสัญญาจะไม่สามารถกู้คืนได้
                </div>
                <p>คุณต้องการลบสัญญาเลขที่ <strong id="contractNumberToDelete"></strong> ใช่หรือไม่?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> ลบสัญญา
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Delete modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const contractId = button.getAttribute('data-contract-id');
                const contractNumber = button.getAttribute('data-contract-number');
                
                const contractNumberElement = deleteModal.querySelector('#contractNumberToDelete');
                const deleteForm = deleteModal.querySelector('#deleteForm');
                
                contractNumberElement.textContent = contractNumber;
                deleteForm.action = `/contract-approvals/${contractId}`;
            });
        }

        // Auto-submit form on filter change
        const filterSelects = document.querySelectorAll('#status, #contract_type, #priority');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection 