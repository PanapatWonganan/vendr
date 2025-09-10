<!-- PR Header Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0"><i class="fas fa-info-circle"></i> ข้อมูลทั่วไป</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-bold" width="40%">เลข PR:</td>
                        <td>{{ $purchaseRequisition->pr_number }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">ชื่อใบขอซื้อ:</td>
                        <td>{{ $purchaseRequisition->title }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">วันที่ขอซื้อ:</td>
                        <td>{{ $purchaseRequisition->request_date ? $purchaseRequisition->request_date->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">วันที่ต้องการ:</td>
                        <td>{{ $purchaseRequisition->required_date ? $purchaseRequisition->required_date->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">สถานะ:</td>
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
                                    'rejected' => 'ปฏิเสธแล้ว'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusClasses[$purchaseRequisition->status] ?? 'secondary' }}">
                                {{ $statusTexts[$purchaseRequisition->status] ?? $purchaseRequisition->status }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">ความเร่งด่วน:</td>
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
                            <span class="badge bg-{{ $priorityClasses[$purchaseRequisition->priority] ?? 'secondary' }}">
                                {{ $priorityTexts[$purchaseRequisition->priority] ?? $purchaseRequisition->priority }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">หมวดหมู่:</td>
                        <td>
                            <span class="badge bg-primary">{{ $purchaseRequisition->category_label }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">ประเภทของงาน:</td>
                        <td>
                            <span class="badge bg-info">{{ $purchaseRequisition->work_type_label }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">วิธีการจัดหา:</td>
                        <td>
                            @if($purchaseRequisition->procurement_method)
                                <span class="badge bg-warning">{{ $purchaseRequisition->procurement_method_label }}</span>
                            @else
                                <span class="text-muted">ไม่ระบุ</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">วงเงินในการจัดหา:</td>
                        <td>
                            @if($purchaseRequisition->procurement_budget)
                                <span class="fw-bold">{{ number_format($purchaseRequisition->procurement_budget, 2) }} บาท</span>
                            @else
                                <span class="text-muted">ไม่ระบุ</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">งวดการส่งมอบ:</td>
                        <td>
                            @if($purchaseRequisition->delivery_schedule)
                                {{ $purchaseRequisition->delivery_schedule }}
                            @else
                                <span class="text-muted">ไม่ระบุ</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">งวดการจ่ายเงิน:</td>
                        <td>
                            @if($purchaseRequisition->payment_schedule)
                                {{ $purchaseRequisition->payment_schedule }}
                            @else
                                <span class="text-muted">ไม่ระบุ</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0"><i class="fas fa-users"></i> ข้อมูลบุคคล</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-bold" width="40%">ผู้ขอ:</td>
                        <td>{{ $purchaseRequisition->requester->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">แผนก:</td>
                        <td>{{ $purchaseRequisition->department->name ?? 'N/A' }}</td>
                    </tr>
                    @if($purchaseRequisition->createdBy && $purchaseRequisition->createdBy->id !== $purchaseRequisition->requester_id)
                    <tr>
                        <td class="fw-bold">บันทึกโดย:</td>
                        <td>{{ $purchaseRequisition->createdBy->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="fw-bold">สกุลเงิน:</td>
                        <td>{{ $purchaseRequisition->currency ?? 'THB' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">ยอดรวม:</td>
                        <td class="fs-5 fw-bold text-primary">
                            {{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency ?? 'THB' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Description -->
@if($purchaseRequisition->description)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0"><i class="fas fa-file-text"></i> เหตุผลที่ขอซื้อ</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $purchaseRequisition->description }}</p>
    </div>
</div>
@endif

<!-- Attachments -->
@if($purchaseRequisition->attachments->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0"><i class="fas fa-paperclip"></i> เอกสารแนบ ({{ $purchaseRequisition->attachments->count() }} ไฟล์)</h6>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($purchaseRequisition->attachments as $attachment)
                <div class="col-md-6 mb-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @php
                                        $fileIcon = 'fas fa-file';
                                        if (str_contains($attachment->file_type, 'pdf')) $fileIcon = 'fas fa-file-pdf text-danger';
                                        elseif (str_contains($attachment->file_type, 'word')) $fileIcon = 'fas fa-file-word text-primary';
                                        elseif (str_contains($attachment->file_type, 'excel') || str_contains($attachment->file_type, 'sheet')) $fileIcon = 'fas fa-file-excel text-success';
                                        elseif (str_contains($attachment->file_type, 'image')) $fileIcon = 'fas fa-file-image text-info';
                                    @endphp
                                    <i class="{{ $fileIcon }} fa-2x"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{ $attachment->original_name }}</h6>
                                    <small class="text-muted">
                                        {{ number_format($attachment->file_size / 1024, 1) }} KB
                                    </small>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewFile({{ $attachment->id }}, '{{ $attachment->original_name }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('purchase-requisitions.download-attachment', $attachment) }}" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Notes -->
@if($purchaseRequisition->notes)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0"><i class="fas fa-sticky-note"></i> หมายเหตุ</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $purchaseRequisition->notes }}</p>
    </div>
</div>
@endif

<!-- Approval History -->
@if($purchaseRequisition->approved_by || $purchaseRequisition->rejected_by)
<div class="card">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0"><i class="fas fa-history"></i> ประวัติการอนุมัติ</h6>
    </div>
    <div class="card-body">
        @if($purchaseRequisition->approved_by)
            <div class="alert alert-success mb-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <i class="fas fa-check-circle"></i> <strong>อนุมัติโดย:</strong> 
                        {{ $purchaseRequisition->approvedBy->name ?? 'N/A' }}
                    </div>
                    <small class="text-muted">
                        {{ $purchaseRequisition->approved_at ? $purchaseRequisition->approved_at->format('d/m/Y H:i') : 'N/A' }}
                    </small>
                </div>
                @if($purchaseRequisition->approval_comments)
                    <div class="mt-2">
                        <strong>ความเห็น:</strong> {{ $purchaseRequisition->approval_comments }}
                    </div>
                @endif
            </div>
        @endif

        @if($purchaseRequisition->rejected_by)
            <div class="alert alert-danger mb-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <i class="fas fa-times-circle"></i> <strong>ปฏิเสธโดย:</strong> 
                        {{ $purchaseRequisition->rejectedBy->name ?? 'N/A' }}
                    </div>
                    <small class="text-muted">
                        {{ $purchaseRequisition->rejected_at ? $purchaseRequisition->rejected_at->format('d/m/Y H:i') : 'N/A' }}
                    </small>
                </div>
                @if($purchaseRequisition->rejection_reason)
                    <div class="mt-2">
                        <strong>เหตุผล:</strong> {{ $purchaseRequisition->rejection_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endif 

                <!-- Committee Members -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">ผู้รับผิดชอบ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label text-muted">คณะกรรมการจัดหาพัสดุ</label>
                                    <div>
                                        @if($purchaseRequisition->procurementCommittee)
                                            <span class="badge bg-primary">{{ $purchaseRequisition->procurementCommittee->name }}</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label text-muted">คณะกรรมการตรวจรับ</label>
                                    <div>
                                        @if($purchaseRequisition->inspectionCommittee)
                                            <span class="badge bg-success">{{ $purchaseRequisition->inspectionCommittee->name }}</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label text-muted">ผู้อนุมัติ PR</label>
                                    <div>
                                        @if($purchaseRequisition->prApprover)
                                            <span class="badge bg-warning">{{ $purchaseRequisition->prApprover->name }}</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label text-muted">ผู้เกี่ยวข้องอื่น</label>
                                    <div>
                                        @if($purchaseRequisition->otherStakeholder)
                                            <span class="badge bg-info">{{ $purchaseRequisition->otherStakeholder->name }}</span>
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- รายการสินค้า -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">รายการสินค้า</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>รายการ</th>
                                        <th>จำนวน</th>
                                        <th>หน่วย</th>
                                        <th>ราคาต่อหน่วย</th>
                                        <th>ราคารวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseRequisition->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $item->description }}
                                            @if($item->specification)
                                                <br><small class="text-muted">{{ $item->specification }}</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->quantity, 0) }}</td>
                                        <td>{{ $item->unit_of_measure ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item->estimated_unit_price ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->estimated_amount ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end">รวมทั้งสิ้น:</td>
                                        <td class="text-end">{{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div> 