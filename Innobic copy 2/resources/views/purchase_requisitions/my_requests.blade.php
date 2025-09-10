@extends('layouts.app')

@section('title', 'ใบ PR ของฉัน')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">ใบ PR ของฉัน</h1>
                    <p class="text-muted mb-0">รายการใบขอซื้อที่ร้องขอโดยคุณ</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('purchase-requisitions.my-requests') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">สถานะ</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอการอนุมัติ</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ถูกปฏิเสธ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="priority" class="form-label">ความเร่งด่วน</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="query" class="form-label">ค้นหา</label>
                            <input type="text" name="query" id="query" class="form-control" 
                                   placeholder="เลข PR หรือชื่อใบขอซื้อ" value="{{ request('query') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="card shadow-sm">
                <div class="card-body">
                    @if($purchaseRequisitions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>เลข PR</th>
                                        <th>ชื่อใบขอซื้อ</th>
                                        <th>แผนก</th>
                                        <th>วันที่ขอ</th>
                                        <th>ความเร่งด่วน</th>
                                        <th>จำนวนเงิน</th>
                                        <th>สถานะ</th>
                                        <th>ผู้บันทึก</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseRequisitions as $pr)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $pr->pr_number }}</strong>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $pr->title }}</div>
                                                @if($pr->description)
                                                    <small class="text-muted">{{ Str::limit($pr->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $pr->department->name ?? 'N/A' }}</td>
                                            <td>{{ $pr->request_date ? $pr->request_date->format('d/m/Y') : 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $priorityClasses = [
                                                        'low' => 'success',
                                                        'medium' => 'warning',
                                                        'high' => 'orange',
                                                        'urgent' => 'danger'
                                                    ];
                                                    $priorityTexts = [
                                                        'low' => 'ต่ำ',
                                                        'medium' => 'ปานกลาง',
                                                        'high' => 'สูง',
                                                        'urgent' => 'เร่งด่วน'
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $priorityClasses[$pr->priority] ?? 'secondary' }}">
                                                    {{ $priorityTexts[$pr->priority] ?? $pr->priority }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ number_format($pr->total_amount, 2) }}</strong>
                                                <small class="text-muted">{{ $pr->currency }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $statusClasses = [
                                                        'draft' => 'secondary',
                                                        'pending' => 'warning',
                                                        'pending_approval' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger'
                                                    ];
                                                    $statusTexts = [
                                                        'draft' => 'แบบร่าง',
                                                        'pending' => 'รอการอนุมัติ',
                                                        'pending_approval' => 'รอการอนุมัติ',
                                                        'approved' => 'อนุมัติแล้ว',
                                                        'rejected' => 'ถูกปฏิเสธ'
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $statusClasses[$pr->status] ?? 'secondary' }}">
                                                    {{ $statusTexts[$pr->status] ?? $pr->status }}
                                                </span>
                                                @if($pr->status === 'approved' && $pr->approved_at)
                                                    <div class="small text-success mt-1">
                                                        อนุมัติเมื่อ {{ $pr->approved_at->format('d/m/Y H:i') }}
                                                    </div>
                                                @elseif($pr->status === 'rejected' && $pr->rejected_at)
                                                    <div class="small text-danger mt-1">
                                                        ปฏิเสธเมื่อ {{ $pr->rejected_at->format('d/m/Y H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($pr->createdBy)
                                                    <div class="fw-semibold">{{ $pr->createdBy->name }}</div>
                                                    <small class="text-muted">{{ $pr->created_at->format('d/m/Y H:i') }}</small>
                                                @else
                                                    <span class="text-muted">ไม่ระบุ</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('purchase-requisitions.show', $pr) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($pr->status === 'draft')
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                onclick="submitForApproval({{ $pr->id }})" title="ส่งขออนุมัติ">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                        <a href="{{ route('purchase-requisitions.edit', $pr) }}" 
                                                           class="btn btn-sm btn-outline-secondary" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if($pr->status === 'rejected' && $pr->rejection_reason)
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="showRejectionReason('{{ addslashes($pr->rejection_reason) }}')" 
                                                                title="ดูเหตุผลการปฏิเสธ">
                                                            <i class="fas fa-exclamation-triangle"></i>
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
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                แสดง {{ $purchaseRequisitions->firstItem() }} ถึง {{ $purchaseRequisitions->lastItem() }} 
                                จาก {{ $purchaseRequisitions->total() }} รายการ
                            </div>
                            {{ $purchaseRequisitions->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">ไม่พบใบ PR</h5>
                            <p class="text-muted">ยังไม่มีใบขอซื้อที่ร้องขอโดยคุณ</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-labelledby="rejectionReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectionReasonModalLabel">
                    <i class="fas fa-times-circle"></i> เหตุผลการปฏิเสธ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    ใบ PR ของคุณถูกปฏิเสธด้วยเหตุผลดังนี้:
                </div>
                <div id="rejectionReasonText" class="p-3 bg-light rounded border-start border-4 border-danger">
                    <!-- Rejection reason will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showRejectionReason(reason) {
        document.getElementById('rejectionReasonText').innerHTML = reason.replace(/\n/g, '<br>');
        const modal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'));
        modal.show();
    }

    function submitForApproval(prId) {
        if (!confirm('คุณต้องการส่งใบ PR นี้ขออนุมัติหรือไม่?')) {
            return;
        }

        const submitBtn = event.target.closest('button');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`/purchase-requisitions/${prId}/submit-for-approval`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
                
                // Reload page after 1.5 seconds
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    }
</script>
@endpush 