@extends('layouts.app')

@section('title', 'PR รอการอนุมัติ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">PR รอการอนุมัติ</h1>
                    <p class="text-muted mb-0">รายการใบขอซื้อที่รอการอนุมัติ</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning text-dark fs-6">
                        {{ $purchaseRequisitions->total() }} รายการ
                    </span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('purchase-requisitions.pending-approvals') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="priority" class="form-label">ความเร่งด่วน</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="department" class="form-label">แผนก</label>
                            <select name="department" id="department" class="form-select">
                                <option value="">ทั้งหมด</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ request('department') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="query" class="form-label">ค้นหา</label>
                            <input type="text" name="query" id="query" class="form-control" 
                                   placeholder="เลข PR, ชื่อใบขอซื้อ หรือชื่อผู้ขอ" value="{{ request('query') }}">
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
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>เลข PR</th>
                                        <th>ชื่อใบขอซื้อ</th>
                                        <th>ผู้ขอ</th>
                                        <th>แผนก</th>
                                        <th>วันที่ขอ</th>
                                        <th>วันที่ต้องการ</th>
                                        <th>ความเร่งด่วน</th>
                                        <th>จำนวนเงิน</th>
                                        <th>จำนวนรายการ</th>
                                        <th class="text-center">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseRequisitions as $pr)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $pr->pr_number }}</span>
                                            </td>
                                            <td>
                                                <div>{{ $pr->title }}</div>
                                                @if($pr->description)
                                                    <small class="text-muted">{{ Str::limit($pr->description, 40) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $pr->requester->name ?? 'N/A' }}</div>
                                                @if($pr->createdBy && $pr->createdBy->id !== $pr->requester_id)
                                                    <small class="text-muted">บันทึกโดย {{ $pr->createdBy->name }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $pr->department->name ?? 'N/A' }}</td>
                                            <td>{{ $pr->request_date ? $pr->request_date->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $pr->required_date ? $pr->required_date->format('d/m/Y') : 'N/A' }}</td>
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
                                                {{ number_format($pr->total_amount, 2) }} {{ $pr->currency }}
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $pr->items->count() }} รายการ
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewPRDetails({{ $pr->id }})" title="ดูรายละเอียด">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="approvePR({{ $pr->id }})" title="อนุมัติ">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="rejectPR({{ $pr->id }})" title="ปฏิเสธ">
                                                        <i class="fas fa-times"></i>
                                                    </button>
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
                            <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">ไม่มี PR ที่รอการอนุมัติ</h5>
                            <p class="text-muted">ยังไม่มีใบขอซื้อที่รออนุมัติในขณะนี้</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PR Details Modal -->
<div class="modal fade" id="prDetailsModal" tabindex="-1" aria-labelledby="prDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="prDetailsModalLabel">
                    <i class="fas fa-file-invoice"></i> รายละเอียดใบ PR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="prDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                        <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-success" id="approveFromModal" onclick="approveFromModal()">
                    <i class="fas fa-check"></i> อนุมัติ
                </button>
                <button type="button" class="btn btn-danger" id="rejectFromModal" onclick="rejectFromModal()">
                    <i class="fas fa-times"></i> ปฏิเสธ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i class="fas fa-check-circle"></i> อนุมัติใบ PR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        คุณกำลังอนุมัติใบ PR นี้ ระบบจะส่งอีเมลแจ้งเตือนให้ผู้ขอทราบ
                    </div>
                    <div class="mb-3">
                        <label for="approval_comments" class="form-label">ความเห็นเพิ่มเติม (ไม่บังคับ)</label>
                        <textarea class="form-control" id="approval_comments" name="approval_comments" 
                                  rows="3" placeholder="ระบุความเห็นหรือข้อสังเกตเพิ่มเติม"></textarea>
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

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectionModalLabel">
                    <i class="fas fa-times-circle"></i> ปฏิเสธใบ PR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        คุณกำลังปฏิเสธใบ PR นี้ ระบบจะส่งอีเมลแจ้งเตือนให้ผู้ขอทราบ
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">เหตุผลในการปฏิเสธ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="4" placeholder="กรุณาระบุเหตุผลในการปฏิเสธ" required></textarea>
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
@endsection

@push('scripts')
<script>
    let currentPRId = null;

    function viewPRDetails(prId) {
        currentPRId = prId;
        const modal = new bootstrap.Modal(document.getElementById('prDetailsModal'));
        
        // Reset content
        document.getElementById('prDetailsContent').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
                <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
            </div>
        `;
        
        modal.show();
        
        // Fetch PR details via AJAX
        fetch(`/purchase-requisitions/${prId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.html) {
                    // Update modal content with clean HTML
                    document.getElementById('prDetailsContent').innerHTML = data.data.html;
                    
                    // Update modal title with PR number
                    document.getElementById('prDetailsModalLabel').innerHTML = `
                        <i class="fas fa-file-invoice"></i> รายละเอียดใบ PR: ${data.data.pr.pr_number}
                    `;
                } else {
                    document.getElementById('prDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ไม่สามารถโหลดข้อมูลได้
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading PR details:', error);
                document.getElementById('prDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาดในการโหลดข้อมูล
                    </div>
                `;
            });
    }

    function approveFromModal() {
        if (currentPRId) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('prDetailsModal'));
            modal.hide();
            approvePR(currentPRId);
        }
    }

    function rejectFromModal() {
        if (currentPRId) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('prDetailsModal'));
            modal.hide();
            rejectPR(currentPRId);
        }
    }

    function approvePR(prId) {
        currentPRId = prId;
        const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
        modal.show();
    }

    function rejectPR(prId) {
        currentPRId = prId;
        document.getElementById('rejection_reason').value = '';
        const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
        modal.show();
    }

    // Handle approval form submission
    document.getElementById('approvalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!currentPRId) return;

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...';

        fetch(`/purchase-requisitions/${currentPRId}/approve`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> อนุมัติ';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> อนุมัติ';
        });
    });

    // Handle rejection form submission
    document.getElementById('rejectionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!currentPRId) return;

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...';

        fetch(`/purchase-requisitions/${currentPRId}/reject`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-times"></i> ปฏิเสธ';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-times"></i> ปฏิเสธ';
        });
    });

    // File viewer function
    function viewFile(fileId, fileName) {
        // Set modal title
        document.getElementById('fileViewerTitle').textContent = fileName;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('fileViewerModal'));
        modal.show();
        
        // Set download button
        const downloadBtn = document.getElementById('fileDownloadBtn');
        downloadBtn.href = `/pr-attachments/${fileId}/download`;
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
                <iframe src="/pr-attachments/${fileId}/view" 
                        style="width: 100%; height: 100%; border: none;"
                        title="${fileName}">
                </iframe>
            `;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(extension)) {
            // For image files
            contentDiv.innerHTML = `
                <div class="text-center p-3" style="height: 100%; overflow: auto;">
                    <img src="/pr-attachments/${fileId}/view" 
                         class="img-fluid" 
                         style="max-height: 100%; max-width: 100%;"
                         alt="${fileName}">
                </div>
            `;
        } else if (['txt', 'csv'].includes(extension)) {
            // For text files
            fetch(`/pr-attachments/${fileId}/view`)
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
</script>
@endpush 