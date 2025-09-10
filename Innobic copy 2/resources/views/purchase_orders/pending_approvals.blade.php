@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>
                        รายการ PO ที่รอการอนุมัติ
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingPOs->total() }}</span>
                    </h5>
                </div>

                <div class="card-body">
                    <!-- Debug Info (เฉพาะในการพัฒนา) -->
                    @if(config('app.debug'))
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>สำหรับการ Debug:</strong> หากปุ่มอนุมัติ/ปฏิเสธไม่ทำงาน กรุณาเปิด Browser Console (กดปุ่ม F12) เพื่อดู error messages
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                    
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="department" class="form-label">แผนก</label>
                            <select name="department" id="department" class="form-select">
                                <option value="">ทุกแผนก</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priority" class="form-label">ความสำคัญ</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="">ทุกระดับ</option>
                                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>สูง</option>
                                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>ต่ำ</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> กรอง
                            </button>
                            <a href="{{ route('purchase-orders.pending-approvals') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> ล้าง
                            </a>
                        </div>
                    </form>

                    @if($pendingPOs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>เลขที่ PO</th>
                                        <th>หัวข้อ</th>
                                        <th>ผู้ขาย</th>
                                        <th>แผนก</th>
                                        <th>ผู้สร้าง</th>
                                        <th>ความสำคัญ</th>
                                        <th>จำนวนเงิน</th>
                                        <th>วันที่สร้าง</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingPOs as $po)
                                        <tr>
                                            <td>
                                                <a href="{{ route('purchase-orders.show', $po) }}" class="fw-bold text-decoration-none">
                                                    {{ $po->po_number }}
                                                </a>
                                            </td>
                                            <td>{{ Str::limit($po->po_title, 40) }}</td>
                                            <td>{{ $po->vendor_name }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $po->department->name }}</span>
                                            </td>
                                            <td>{{ $po->creator->name }}</td>
                                            <td>
                                                <span class="badge {{ $po->priority_badge }}">{{ $po->priority_text }}</span>
                                            </td>
                                            <td>{{ number_format($po->total_amount, 2) }} {{ $po->currency }}</td>
                                            <td>{{ $po->created_at ? $po->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-success btn-sm approve-btn" 
                                                            data-po-id="{{ $po->id }}" 
                                                            data-po-number="{{ $po->po_number }}"
                                                            onclick="approveQuick({{ $po->id }}, '{{ $po->po_number }}')">
                                                        <i class="fas fa-check"></i> อนุมัติ
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm reject-btn" 
                                                            data-po-id="{{ $po->id }}" 
                                                            data-po-number="{{ $po->po_number }}"
                                                            onclick="rejectQuick({{ $po->id }}, '{{ $po->po_number }}')">
                                                        <i class="fas fa-times"></i> ปฏิเสธ
                                                    </button>
                                                    <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> ดู
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        {{ $pendingPOs->links() }}
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">ไม่มี PO ที่รอการอนุมัติ</h4>
                            <p class="text-muted">ขณะนี้ไม่มีใบ Purchase Order ที่รอการอนุมัติจากคุณ</p>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-primary">
                                <i class="fas fa-list"></i> ดู PO ทั้งหมด
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    ยืนยันการอนุมัติ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการอนุมัติ PO <strong id="approve-po-number"></strong> หรือไม่?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    การอนุมัติจะทำให้ PO มีสถานะเป็น "อนุมัติแล้ว" และสามารถส่งให้ผู้ขายได้
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" onclick="submitApproval()">
                    <i class="fas fa-check"></i> อนุมัติ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    ปฏิเสธ PO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>คุณต้องการปฏิเสธ PO <strong id="reject-po-number"></strong> หรือไม่?</p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">เหตุผลในการปฏิเสธ <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" 
                                  placeholder="กรุณาระบุเหตุผลในการปฏิเสธ..." required></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        การปฏิเสธจะทำให้ PO กลับไปเป็นสถานะ "ถูกปฏิเสธ" และผู้สร้างสามารถแก้ไขได้
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="redirect_to" value="pending">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> ปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Global variables for modals
let approveModal, rejectModal, currentPOId;

// Initialize modals and event listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking Bootstrap...');
    
    // Wait for Bootstrap to be available
    function initializeModals() {
        if (typeof bootstrap !== 'undefined') {
            console.log('Bootstrap is available, initializing modals...');
            
            const approveModalElement = document.getElementById('approveModal');
            const rejectModalElement = document.getElementById('rejectModal');
            
            if (approveModalElement) {
                approveModal = new bootstrap.Modal(approveModalElement);
                console.log('Approve modal initialized');
            } else {
                console.error('Approve modal element not found');
            }
            
            if (rejectModalElement) {
                rejectModal = new bootstrap.Modal(rejectModalElement);
                console.log('Reject modal initialized');
            } else {
                console.error('Reject modal element not found');
            }
            
            // Add event listeners for buttons
            setupEventListeners();
            
            console.log('All modals initialized successfully');
        } else {
            console.log('Bootstrap not ready, retrying in 100ms...');
            setTimeout(initializeModals, 100);
        }
    }
    
    initializeModals();
});

// Setup event listeners for approve and reject buttons
function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    // Add event listeners to all approve buttons
    document.querySelectorAll('.approve-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const poId = this.getAttribute('data-po-id');
            const poNumber = this.getAttribute('data-po-number');
            console.log('Approve button clicked:', poId, poNumber);
            approveQuick(poId, poNumber);
        });
    });
    
    // Add event listeners to all reject buttons
    document.querySelectorAll('.reject-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const poId = this.getAttribute('data-po-id');
            const poNumber = this.getAttribute('data-po-number');
            console.log('Reject button clicked:', poId, poNumber);
            rejectQuick(poId, poNumber);
        });
    });
    
    console.log('Event listeners setup complete');
}

function approveQuick(poId, poNumber) {
    console.log('approveQuick called:', poId, poNumber);
    
    try {
        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded');
            alert('เกิดข้อผิดพลาด: Bootstrap ยังไม่พร้อมใช้งาน');
            return;
        }
        
        const approveNumberElement = document.getElementById('approve-po-number');
        const approveModalElement = document.getElementById('approveModal');
        
        if (!approveNumberElement) {
            console.error('approve-po-number element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ element สำหรับเลข PO');
            return;
        }
        
        if (!approveModalElement) {
            console.error('approveModal element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับอนุมัติ');
            return;
        }
        
        // Set data for approval
        approveNumberElement.textContent = poNumber;
        currentPOId = poId; // Store current PO ID for submission
        
        console.log('Set current PO ID:', currentPOId);
        
        // Show modal
        if (approveModal) {
            console.log('Showing modal using global instance');
            approveModal.show();
        } else {
            console.log('Creating new modal instance');
            const modal = new bootstrap.Modal(approveModalElement);
            modal.show();
        }
        
    } catch (error) {
        console.error('Error in approveQuick:', error);
        alert('เกิดข้อผิดพลาด: ' + error.message);
    }
}

function rejectQuick(poId, poNumber) {
    console.log('rejectQuick called:', poId, poNumber);
    
    try {
        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded');
            alert('เกิดข้อผิดพลาด: Bootstrap ยังไม่พร้อมใช้งาน');
            return;
        }
        
        const rejectNumberElement = document.getElementById('reject-po-number');
        const rejectFormElement = document.getElementById('rejectForm');
        const rejectionReasonElement = document.getElementById('rejection_reason');
        const rejectModalElement = document.getElementById('rejectModal');
        
        if (!rejectNumberElement) {
            console.error('reject-po-number element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ element สำหรับเลข PO');
            return;
        }
        
        if (!rejectFormElement) {
            console.error('rejectForm element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ form สำหรับปฏิเสธ');
            return;
        }
        
        if (!rejectionReasonElement) {
            console.error('rejection_reason element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ field สำหรับเหตุผล');
            return;
        }
        
        if (!rejectModalElement) {
            console.error('rejectModal element not found');
            alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับปฏิเสธ');
            return;
        }
        
        // Set form data
        rejectNumberElement.textContent = poNumber;
        rejectFormElement.action = `/purchase-orders/${poId}/reject`;
        rejectionReasonElement.value = '';
        
        console.log('Form action set to:', rejectFormElement.action);
        
        // Show modal
        if (rejectModal) {
            console.log('Showing modal using global instance');
            rejectModal.show();
        } else {
            console.log('Creating new modal instance');
            const modal = new bootstrap.Modal(rejectModalElement);
            modal.show();
        }
        
    } catch (error) {
        console.error('Error in rejectQuick:', error);
        alert('เกิดข้อผิดพลาด: ' + error.message);
    }
}

// Debug function to check if everything is loaded
function debugModals() {
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('Approve modal element:', document.getElementById('approveModal'));
    console.log('Reject modal element:', document.getElementById('rejectModal'));
    console.log('Approve modal instance:', approveModal);
    console.log('Reject modal instance:', rejectModal);
}

// Submit approval using AJAX like PR system
function submitApproval() {
    if (!currentPOId) {
        alert('ไม่พบ PO ID');
        return;
    }

    const submitBtn = document.querySelector('#approveModal .btn-success');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...';
    }

    fetch(`/purchase-orders/${currentPOId}/approve`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            redirect_to: 'pending'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('approveModal'));
            modal.hide();
            
            // Reload page to show updated list
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> อนุมัติ';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> อนุมัติ';
        }
    });
}

// Call debug function after a short delay
setTimeout(debugModals, 1000);
</script>
@endpush
@endsection 