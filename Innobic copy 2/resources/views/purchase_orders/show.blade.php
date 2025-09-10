@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        รายละเอียดใบ PO: {{ $po->po_number }}
                    </h3>
                    <div class="d-flex gap-2">
                        @if($po->canEdit())
                            <a href="{{ route('purchase-orders.edit', $po) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>แก้ไข
                            </a>
                        @endif
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>กลับ
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status & Actions Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-3"><strong>สถานะปัจจุบัน:</strong></span>
                                <span class="badge {{ $po->status_badge }} fs-6 rounded-pill">
                                    {{ $po->status_text }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="me-3"><strong>ระดับความสำคัญ:</strong></span>
                                <span class="badge {{ $po->priority_badge }} fs-6 rounded-pill">
                                    {{ $po->priority_text }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <!-- Action Buttons -->
                            @if($po->status === 'draft')
                                <button type="button" class="btn btn-success me-2" onclick="submitForApproval()">
                                    <i class="fas fa-paper-plane me-2"></i>ส่งขออนุมัติ
                                </button>
                            @endif

                            @if($po->canApprove() && (auth()->user()->isAdmin() || auth()->user()->hasRole('procurement_manager') || (auth()->user()->hasRole('department_head') && auth()->user()->department_id == $po->department_id)))
                                <button type="button" class="btn btn-success me-2" onclick="showApproveModal()">
                                    <i class="fas fa-check me-2"></i>อนุมัติ
                                </button>
                                <button type="button" class="btn btn-danger me-2" onclick="showRejectModal()">
                                    <i class="fas fa-times me-2"></i>ปฏิเสธ
                                </button>
                            @endif

                            @if($po->canSendToVendor())
                                <button type="button" class="btn btn-info me-2" onclick="sendToVendor()">
                                    <i class="fas fa-share me-2"></i>ส่งให้ผู้ขาย
                                </button>
                            @endif

                            @if($po->canMarkReceived())
                                <button type="button" class="btn btn-primary me-2" onclick="showReceiveModal()">
                                    <i class="fas fa-box me-2"></i>บันทึกการรับของ
                                </button>
                            @endif

                            @if($po->status === 'fully_received')
                                <button type="button" class="btn btn-dark me-2" onclick="closePO()">
                                    <i class="fas fa-times-circle me-2"></i>ปิดงาน
                                </button>
                            @endif

                            @if(!in_array($po->status, ['closed', 'cancelled']))
                                <button type="button" class="btn btn-danger" onclick="showCancelModal()">
                                    <i class="fas fa-ban me-2"></i>ยกเลิก
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- PO Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>ข้อมูลใบ PO
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th class="text-muted" width="40%">เลข PO:</th>
                                            <td><strong>{{ $po->po_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">ชื่อใบ PO:</th>
                                            <td>{{ $po->po_title ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">ผู้ขาย:</th>
                                            <td>{{ $po->vendor_name ?? $po->supplier?->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">ข้อมูลติดต่อ:</th>
                                            <td>{{ $po->vendor_contact ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">แผนก:</th>
                                            <td>{{ $po->department->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th class="text-muted" width="40%">จำนวนเงิน:</th>
                                            <td><strong>{{ number_format($po->total_amount, 2) }} {{ $po->currency }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">วันที่สั่งซื้อ:</th>
                                            <td>{{ $po->order_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">วันที่คาดว่าจะส่งมอบ:</th>
                                            <td>{{ $po->expected_delivery_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">เงื่อนไขการชำระ:</th>
                                            <td>{{ $po->payment_terms ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">สร้างโดย:</th>
                                            <td>{{ $po->creator->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($po->description)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-2">รายละเอียด:</h6>
                                        <p class="mb-0">{{ $po->description }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($po->delivery_address)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-2">ที่อยู่ส่งมอบ:</h6>
                                        <p class="mb-0">{{ $po->delivery_address }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($po->notes)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-2">หมายเหตุ:</h6>
                                        <p class="mb-0">{{ $po->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Files -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-paperclip me-2"></i>ไฟล์เอกสารแนบ
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($po->files->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ชื่อไฟล์</th>
                                                <th>หมวดหมู่</th>
                                                <th>ขนาด</th>
                                                <th>อัพโหลดโดย</th>
                                                <th>วันที่</th>
                                                <th>การจัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($po->files as $file)
                                                <tr>
                                                    <td>
                                                        <i class="fas {{ $file->isPdf() ? 'fa-file-pdf text-danger' : 'fa-file-image text-primary' }} me-2"></i>
                                                        {{ $file->original_name }}
                                                        @if($file->description)
                                                            <br><small class="text-muted">{{ $file->description }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary rounded-pill">
                                                            {{ $file->file_category_text }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $file->file_size_human }}</td>
                                                    <td>{{ $file->uploader->name }}</td>
                                                    <td>{{ $file->created_at ? $file->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-info" 
                                                                    onclick="viewFile({{ $file->id }}, '{{ $file->original_name }}')" title="ดูไฟล์">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="{{ route('po-files.download', $file) }}" 
                                                               class="btn btn-outline-primary" title="ดาวน์โหลด">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            @if($po->canEdit())
                                                                <button type="button" class="btn btn-outline-danger" 
                                                                        onclick="deleteFile({{ $file->id }})" title="ลบ">
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
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">ไม่มีไฟล์เอกสารแนบ</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Timeline & Actions -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>ประวัติการดำเนินการ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">สร้างใบ PO</h6>
                                        <p class="mb-1 text-muted">โดย {{ $po->creator->name }}</p>
                                        <small class="text-muted">{{ $po->created_at ? $po->created_at->format('d/m/Y H:i') : 'N/A' }}</small>
                                    </div>
                                </div>

                                @if($po->approved_at)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">อนุมัติแล้ว</h6>
                                            <p class="mb-1 text-muted">โดย {{ $po->approver->name ?? 'N/A' }}</p>
                                            <small class="text-muted">{{ $po->approved_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @endif

                                @if($po->sent_at)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">ส่งให้ผู้ขายแล้ว</h6>
                                            <small class="text-muted">{{ $po->sent_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @endif

                                @if($po->acknowledged_at)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">ผู้ขายรับทราบแล้ว</h6>
                                            <small class="text-muted">{{ $po->acknowledged_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @endif

                                @if($po->closed_at)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-dark"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">ปิดงานแล้ว</h6>
                                            <small class="text-muted">{{ $po->closed_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @endif

                                @if($po->status === 'cancelled')
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-danger"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">ยกเลิกแล้ว</h6>
                                            @if($po->cancellation_reason)
                                                <p class="mb-1 text-muted">{{ $po->cancellation_reason }}</p>
                                            @endif
                                            <small class="text-muted">{{ $po->updated_at ? $po->updated_at->format('d/m/Y H:i') : 'N/A' }}</small>
                                        </div>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    อนุมัติใบ PO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-orders.approve', $po) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>คุณต้องการอนุมัติใบ PO <strong>{{ $po->po_number }}</strong> หรือไม่?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        การอนุมัติจะทำให้ PO มีสถานะเป็น "อนุมัติแล้ว" และสามารถส่งให้ผู้ขายได้
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> อนุมัติ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    ปฏิเสธใบ PO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-orders.reject', $po) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>คุณต้องการปฏิเสธใบ PO <strong>{{ $po->po_number }}</strong> หรือไม่?</p>
                    
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> ปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">บันทึกการรับของ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-orders.mark-received', $po) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">สถานะการรับของ</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_fully_received" value="1" id="fully_received" checked>
                                <label class="form-check-label" for="fully_received">
                                    รับครบแล้ว
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_fully_received" value="0" id="partially_received">
                                <label class="form-check-label" for="partially_received">
                                    รับบางส่วน
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยกเลิกใบ PO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-orders.cancel', $po) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">เหตุผลในการยกเลิก <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- File Viewer Modal -->
<div class="modal fade" id="fileViewerModal" tabindex="-1" aria-labelledby="fileViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileViewerModalLabel">
                    <i class="fas fa-file me-2"></i>
                    <span id="fileViewerTitle">ดูไฟล์</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="fileViewerContent" style="height: 80vh;">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <a id="fileDownloadBtn" href="#" class="btn btn-primary" download>
                    <i class="fas fa-download me-1"></i>ดาวน์โหลด
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -37px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content h6 {
    font-weight: 600;
    color: #2d3748;
}
</style>
@endpush

@push('scripts')
<script>
function submitForApproval() {
    if (confirm('คุณต้องการส่งใบ PO นี้เพื่อขออนุมัติหรือไม่?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("purchase-orders.submit-for-approval", $po) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function showApproveModal() {
    // Wait for page to be ready
    function waitAndExecute() {
        if (document.readyState === 'complete' && typeof bootstrap !== 'undefined') {
            try {
                const modalElement = document.getElementById('approveModal');
                
                if (!modalElement) {
                    alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับอนุมัติ');
                    return;
                }
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in showApproveModal:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        } else {
            setTimeout(waitAndExecute, 100);
        }
    }
    waitAndExecute();
}

function showRejectModal() {
    // Wait for page to be ready
    function waitAndExecute() {
        if (document.readyState === 'complete' && typeof bootstrap !== 'undefined') {
            try {
                const modalElement = document.getElementById('rejectModal');
                const reasonElement = document.getElementById('rejection_reason');
                
                if (!modalElement || !reasonElement) {
                    alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับปฏิเสธ');
                    return;
                }
                
                reasonElement.value = '';
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in showRejectModal:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        } else {
            setTimeout(waitAndExecute, 100);
        }
    }
    waitAndExecute();
}

function sendToVendor() {
    if (confirm('คุณต้องการส่งใบ PO นี้ให้ผู้ขายหรือไม่?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("purchase-orders.send-to-vendor", $po) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function showReceiveModal() {
    // Wait for page to be ready
    function waitAndExecute() {
        if (document.readyState === 'complete' && typeof bootstrap !== 'undefined') {
            try {
                const modalElement = document.getElementById('receiveModal');
                
                if (!modalElement) {
                    alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับบันทึกการรับของ');
                    return;
                }
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in showReceiveModal:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        } else {
            setTimeout(waitAndExecute, 100);
        }
    }
    waitAndExecute();
}

function closePO() {
    if (confirm('คุณต้องการปิดงานใบ PO นี้หรือไม่?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("purchase-orders.close", $po) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function showCancelModal() {
    // Wait for page to be ready
    function waitAndExecute() {
        if (document.readyState === 'complete' && typeof bootstrap !== 'undefined') {
            try {
                const modalElement = document.getElementById('cancelModal');
                
                if (!modalElement) {
                    alert('เกิดข้อผิดพลาด: ไม่พบ modal สำหรับยกเลิก');
                    return;
                }
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in showCancelModal:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        } else {
            setTimeout(waitAndExecute, 100);
        }
    }
    waitAndExecute();
}

function viewFile(fileId, fileName) {
    // Set modal title
    document.getElementById('fileViewerTitle').textContent = fileName;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('fileViewerModal'));
    modal.show();
    
    // Set download button
    const downloadBtn = document.getElementById('fileDownloadBtn');
    downloadBtn.href = `/po-files/${fileId}/download`;
    downloadBtn.download = fileName;
    
    // Load file content
    const contentDiv = document.getElementById('fileViewerContent');
    contentDiv.innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
        </div>
    `;
    
    // Get file extension
    const extension = fileName.split('.').pop().toLowerCase();
    
    if (['pdf'].includes(extension)) {
        // For PDF files
        contentDiv.innerHTML = `
            <iframe src="/po-files/${fileId}/view" 
                    style="width: 100%; height: 100%; border: none;"
                    title="${fileName}">
            </iframe>
        `;
    } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
        // For image files
        contentDiv.innerHTML = `
            <div class="text-center p-3" style="height: 100%; overflow: auto;">
                <img src="/po-files/${fileId}/view" 
                     class="img-fluid" 
                     style="max-height: 100%; max-width: 100%;"
                     alt="${fileName}">
            </div>
        `;
    } else if (['txt', 'csv'].includes(extension)) {
        // For text files
        fetch(`/po-files/${fileId}/view`)
            .then(response => response.text())
            .then(text => {
                contentDiv.innerHTML = `
                    <div class="p-3" style="height: 100%; overflow: auto;">
                        <pre style="white-space: pre-wrap; font-family: monospace;">${text}</pre>
                    </div>
                `;
            })
            .catch(error => {
                contentDiv.innerHTML = `
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-muted">ไม่สามารถแสดงไฟล์นี้ได้</p>
                            <p class="text-muted small">กรุณาดาวน์โหลดเพื่อดูไฟล์</p>
                        </div>
                    </div>
                `;
            });
    } else {
        // For unsupported files
        contentDiv.innerHTML = `
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="text-center">
                    <i class="fas fa-file fa-3x text-muted mb-3"></i>
                    <p class="text-muted">ไม่รองรับการแสดงไฟล์ประเภทนี้</p>
                    <p class="text-muted small">กรุณาดาวน์โหลดเพื่อดูไฟล์</p>
                </div>
            </div>
        `;
    }
}

function deleteFile(fileId) {
    if (confirm('คุณต้องการลบไฟล์นี้หรือไม่?')) {
        fetch(`/po-files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการลบไฟล์');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการลบไฟล์');
        });
    }
}
</script>
@endpush
@endsection 