@extends('layouts.app')

@section('title', 'รายการใบขอซื้อ')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>รายการใบขอซื้อ</h1>
                @if(auth()->user()->hasPermission('purchase_requisition.create') || auth()->user()->roles->contains('name', 'requester') || auth()->user()->roles->contains('name', 'admin'))
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus me-1"></i> สร้างใบขอซื้อ
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('purchase-requisitions.create') }}">
                                <i class="fas fa-file-alt me-2 text-primary"></i> PR ปกติ (ต้องทำ PO)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('purchase-requisitions.create-direct-small') }}">
                                <i class="fas fa-shopping-cart me-2 text-success"></i> จัดซื้อตรง ≤10,000 บาท
                                <small class="text-muted d-block">ไม่ต้องทำ PO</small>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('purchase-requisitions.create-direct-medium') }}">
                                <i class="fas fa-shopping-basket me-2 text-warning"></i> จัดซื้อตรง ≤100,000 บาท
                                <small class="text-muted d-block">ไม่ต้องทำ PO</small>
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Filter section -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-filter me-1"></i> ตัวกรอง
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('purchase-requisitions.index') }}" method="GET" id="filter-form">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                            <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>รออนุมัติ</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ไม่อนุมัติ</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="priority" class="form-label">ความสำคัญ</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                            <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="category" class="form-label">หมวดหมู่</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">ทั้งหมด</option>
                            @foreach(\App\Models\PurchaseRequisition::getCategoryOptions() as $value => $label)
                            <option value="{{ $value }}" {{ request('category') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="work_type" class="form-label">ประเภทของงาน</label>
                        <select name="work_type" id="work_type" class="form-select">
                            <option value="">ทั้งหมด</option>
                            @foreach(\App\Models\PurchaseRequisition::getWorkTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ request('work_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="procurement_method" class="form-label">วิธีการจัดหา</label>
                        <select name="procurement_method" id="procurement_method" class="form-select">
                            <option value="">ทั้งหมด</option>
                            @foreach(\App\Models\PurchaseRequisition::getProcurementMethodOptions() as $value => $label)
                            <option value="{{ $value }}" {{ request('procurement_method') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="department" class="form-label">แผนก</label>
                        <select name="department" id="department" class="form-select">
                            <option value="">ทั้งหมด</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="query" class="form-label">ค้นหา</label>
                        <div class="input-group">
                            <input type="text" name="query" id="query" class="form-control" placeholder="เลขที่, หัวข้อ, รายละเอียด..." value="{{ request('query') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-redo me-1"></i> รีเซ็ต
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> กรอง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase Requisitions List -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-1"></i> รายการใบขอซื้อทั้งหมด
                <span class="badge bg-primary ms-2">{{ $purchaseRequisitions->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>เลขที่ใบขอซื้อ</th>
                            <th>ผู้ขอ</th>
                            <th>หมวดหมู่</th>
                            <th>ประเภทของงาน</th>
                            <th>วิธีการจัดหา</th>
                            <th>วันที่ขอ</th>
                            <th>จำนวนเงิน</th>
                            <th>สถานะ</th>
                            <th>ความสำคัญ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseRequisitions as $pr)
                            <tr>
                                <td>{{ $pr->pr_number }}</td>
                                <td>{{ $pr->requester->name ?? 'ไม่ระบุผู้ขอ' }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $pr->category_label }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $pr->work_type_label }}</span>
                                </td>
                                <td>
                                    @if($pr->procurement_method)
                                        <span class="badge bg-success">{{ $pr->procurement_method_label }}</span>
                                    @else
                                        <span class="text-muted">ไม่ระบุ</span>
                                    @endif
                                </td>
                                <td>{{ $pr->date ? \Carbon\Carbon::parse($pr->date)->format('d/m/Y') : 'ไม่ระบุ' }}</td>
                                <td>{{ number_format($pr->total_amount, 2) }} บาท</td>
                                <td>
                                    @if ($pr->status === 'pending' || $pr->status === 'pending_approval')
                                        <span class="badge bg-warning">รอการอนุมัติ</span>
                                    @elseif ($pr->status === 'approved')
                                        <span class="badge bg-success">อนุมัติแล้ว</span>
                                    @elseif ($pr->status === 'rejected')
                                        <span class="badge bg-danger">ไม่อนุมัติ</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($pr->priority === 'high')
                                        <span class="badge bg-danger">สูง</span>
                                    @elseif ($pr->priority === 'medium')
                                        <span class="badge bg-warning">ปานกลาง</span>
                                    @else
                                        <span class="badge bg-success">ต่ำ</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('purchase-requisitions.show', $pr) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> ดู
                                    </a>
                                    @if(in_array($pr->status, ['draft', 'rejected']) && 
                                        (auth()->user()->hasRole('admin') || auth()->id() == $pr->requester_id))
                                    <a href="{{ route('purchase-requisitions.edit', $pr) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    @endif
                                    
                                    @if(in_array($pr->status, ['draft', 'rejected']) && 
                                        (auth()->user()->hasRole('admin') || auth()->id() == $pr->requester_id))
                                    <button type="button" class="btn btn-sm btn-danger delete-pr" 
                                            data-pr-id="{{ $pr->id }}" 
                                            data-pr-number="{{ $pr->pr_number }}"
                                            data-pr-title="{{ $pr->title }}"
                                            data-pr-status="{{ $pr->status }}">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    แสดง {{ $purchaseRequisitions->firstItem() ?? 0 }} ถึง {{ $purchaseRequisitions->lastItem() ?? 0 }} จากทั้งหมด {{ $purchaseRequisitions->total() }} รายการ
                </div>
                <div>
                    {{ $purchaseRequisitions->links() }}
                </div>
            </div>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> ยืนยันการลบใบขอซื้อ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i> การลบใบขอซื้อจะไม่สามารถกู้คืนได้
                </div>
                <div class="mb-3">
                    <strong>รายละเอียดใบขอซื้อที่จะลบ:</strong>
                </div>
                <div class="row">
                    <div class="col-4"><strong>เลขที่:</strong></div>
                    <div class="col-8"><span id="prNumberToDelete"></span></div>
                </div>
                <div class="row">
                    <div class="col-4"><strong>หัวข้อ:</strong></div>
                    <div class="col-8"><span id="prTitleToDelete"></span></div>
                </div>
                <div class="row">
                    <div class="col-4"><strong>สถานะ:</strong></div>
                    <div class="col-8"><span id="prStatusToDelete"></span></div>
                </div>
                <hr>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i> คุณต้องการลบใบขอซื้อนี้ใช่หรือไม่?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> ยกเลิก
                </button>
                <form id="deleteForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> ลบใบขอซื้อ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function viewFile(fileId, fileName) {
        console.log('viewFile called with:', fileId, fileName);
        
        // Set modal title
        document.getElementById('fileViewerTitle').textContent = fileName;
        
        // Show modal
        try {
            const modal = new bootstrap.Modal(document.getElementById('fileViewerModal'));
            modal.show();
        } catch (error) {
            console.error('Error showing modal:', error);
            alert('เกิดข้อผิดพลาดในการเปิด modal');
            return;
        }
        
        // Set download button
        const downloadBtn = document.getElementById('fileDownloadBtn');
        downloadBtn.href = `/purchase-requisitions/attachments/${fileId}/download`;
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
            console.log('Loading PDF:', `/pr-attachments/${fileId}/view`);
            contentDiv.innerHTML = `
                <iframe src="/pr-attachments/${fileId}/view" 
                        style="width: 100%; height: 100%; border: none;"
                        title="${fileName}"
                        onload="console.log('PDF iframe loaded successfully')"
                        onerror="console.log('PDF iframe failed to load')">
                </iframe>
            `;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
            // For image files
            console.log('Loading image:', `/pr-attachments/${fileId}/view`);
            contentDiv.innerHTML = `
                <div class="text-center p-3" style="height: 100%; overflow: auto;">
                    <img src="/pr-attachments/${fileId}/view" 
                         class="img-fluid" 
                         style="max-height: 100%; max-width: 100%;"
                         alt="${fileName}"
                         onload="console.log('Image loaded successfully')"
                         onerror="console.log('Image failed to load')">
                </div>
            `;
        } else if (['txt', 'csv'].includes(extension)) {
            // For text files
            console.log('Loading text file:', `/pr-attachments/${fileId}/view`);
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

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Handle delete button clicks
        const deleteButtons = document.querySelectorAll('.delete-pr');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const deleteForm = document.getElementById('deleteForm');
        const prNumberToDelete = document.getElementById('prNumberToDelete');
        const prTitleToDelete = document.getElementById('prTitleToDelete');
        const prStatusToDelete = document.getElementById('prStatusToDelete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prId = this.getAttribute('data-pr-id');
                const prNumber = this.getAttribute('data-pr-number');
                const prTitle = this.getAttribute('data-pr-title');
                const prStatus = this.getAttribute('data-pr-status');
                
                // แปลงสถานะเป็นภาษาไทย
                const statusTranslation = {
                    'draft': 'ร่าง',
                    'rejected': 'ถูกปฏิเสธ',
                    'pending_approval': 'รออนุมัติ',
                    'approved': 'อนุมัติแล้ว'
                };
                
                deleteForm.action = `/purchase-requisitions/${prId}`;
                prNumberToDelete.textContent = prNumber;
                prTitleToDelete.textContent = prTitle;
                prStatusToDelete.textContent = statusTranslation[prStatus] || prStatus;
                
                deleteModal.show();
            });
        });
        
        // Filter form auto-submit when select fields change
        const filterSelects = document.querySelectorAll('#status, #priority, #department, #work_type, #procurement_method');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });
    });
</script>
@endpush 