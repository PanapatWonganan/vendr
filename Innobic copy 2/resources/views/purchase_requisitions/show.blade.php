@extends('layouts.app')

@section('title', 'รายละเอียดใบขอซื้อ')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>รายละเอียดใบขอซื้อ</h1>
                    <p class="text-muted">เลขที่: {{ $purchaseRequisition->pr_number }}</p>
                </div>
                <div class="d-flex">
                    <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> กลับไปยังรายการ
                    </a>
                    
                    @if($purchaseRequisition->status === 'draft' && 
                        (auth()->user()->isAdmin() || auth()->id() == $purchaseRequisition->requester_id))
                    <button type="button" class="btn btn-success me-2" onclick="submitForApproval({{ $purchaseRequisition->id }})">
                        <i class="fas fa-paper-plane me-1"></i> ส่งขออนุมัติ
                    </button>
                    @endif
                    
                    @if($purchaseRequisition->status === 'approved' && auth()->user()->isAdmin() && $purchaseRequisition->requiresPO())
                    <a href="{{ route('purchase-orders.create-from-pr', $purchaseRequisition) }}" class="btn btn-primary me-2">
                        <i class="fas fa-shopping-cart me-1"></i> สร้างใบ PO
                    </a>
                    @endif
                    
                    @if((auth()->user()->hasRole('admin') || auth()->id() == $purchaseRequisition->requester_id) && 
                        in_array($purchaseRequisition->status, ['draft', 'rejected']))
                    <a href="{{ route('purchase-requisitions.edit', $purchaseRequisition) }}" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-1"></i> แก้ไข
                    </a>
                    @endif
                    
                    @if((auth()->user()->hasRole('admin') || auth()->id() == $purchaseRequisition->requester_id) && 
                        in_array($purchaseRequisition->status, ['draft', 'rejected']))
                    <button type="button" class="btn btn-danger delete-pr" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal" 
                        data-pr-id="{{ $purchaseRequisition->id }}" 
                        data-pr-number="{{ $purchaseRequisition->pr_number }}"
                        data-pr-title="{{ $purchaseRequisition->title }}">
                        <i class="fas fa-trash me-1"></i> ลบ
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Main PR details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-1"></i> ข้อมูลใบขอซื้อ
                    </h5>
                    <div>
                        @php
                        $statusClasses = [
                            'draft' => 'bg-secondary',
                            'pending' => 'bg-warning text-dark',
                            'pending_approval' => 'bg-warning text-dark',
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'processing' => 'bg-info text-dark',
                            'completed' => 'bg-primary',
                            'cancelled' => 'bg-dark',
                        ];
                        $statusLabels = [
                            'draft' => 'แบบร่าง',
                            'pending' => 'รออนุมัติ',
                            'pending_approval' => 'รออนุมัติ',
                            'approved' => 'อนุมัติแล้ว',
                            'rejected' => 'ไม่อนุมัติ',
                            'processing' => 'กำลังดำเนินการ',
                            'completed' => 'เสร็จสิ้น',
                            'cancelled' => 'ยกเลิก',
                        ];
                        $priorityClasses = [
                            'low' => 'bg-success',
                            'medium' => 'bg-info text-dark',
                            'high' => 'bg-warning text-dark',
                            'urgent' => 'bg-danger',
                        ];
                        $priorityLabels = [
                            'low' => 'ต่ำ',
                            'medium' => 'ปานกลาง',
                            'high' => 'สูง',
                            'urgent' => 'เร่งด่วน',
                        ];
                        @endphp
                        <span class="badge {{ $statusClasses[$purchaseRequisition->status] }} me-1">
                            {{ $statusLabels[$purchaseRequisition->status] }}
                        </span>
                        <span class="badge {{ $priorityClasses[$purchaseRequisition->priority] }} me-1">
                            {{ $priorityLabels[$purchaseRequisition->priority] }}
                        </span>
                        @if($purchaseRequisition->isDirectPurchase())
                        <span class="badge bg-info">
                            {{ $purchaseRequisition->pr_type_label }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>{{ $purchaseRequisition->title }}</h5>
                            @if($purchaseRequisition->category)
                                <div class="mb-2">
                                    <span class="badge bg-info">{{ $purchaseRequisition->category_label }}</span>
                                </div>
                            @endif
                            @if($purchaseRequisition->work_type)
                                <div class="mb-2">
                                    <span class="badge bg-success">{{ $purchaseRequisition->work_type_label }}</span>
                                </div>
                            @endif
                            @if($purchaseRequisition->procurement_method)
                                <div class="mb-2">
                                    <span class="badge bg-warning">{{ $purchaseRequisition->procurement_method_label }}</span>
                                </div>
                            @endif
                            <p>{{ $purchaseRequisition->description ?? 'ไม่มีคำอธิบายเพิ่มเติม' }}</p>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">แผนก</label>
                                        <div>{{ $purchaseRequisition->department->name ?? 'ไม่ระบุแผนก' }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">ผู้ขอ</label>
                                        <div>{{ $purchaseRequisition->requester->name ?? 'ไม่ระบุผู้ขอ' }}</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">วันที่สร้าง</label>
                                        <div>{{ $purchaseRequisition->created_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">วันที่ต้องการ</label>
                                        <div>{{ $purchaseRequisition->required_date->format('d/m/Y') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional fields -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">วงเงินในการจัดหา</label>
                                <div>
                                    @if($purchaseRequisition->procurement_budget)
                                        <span class="fw-bold">{{ number_format($purchaseRequisition->procurement_budget, 2) }} บาท</span>
                                    @else
                                        <span class="text-muted">ไม่ระบุ</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">งวดการส่งมอบ</label>
                                <div>
                                    @if($purchaseRequisition->delivery_schedule)
                                        {{ $purchaseRequisition->delivery_schedule }}
                                    @else
                                        <span class="text-muted">ไม่ระบุ</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">งวดการจ่ายเงิน</label>
                                <div>
                                    @if($purchaseRequisition->payment_schedule)
                                        {{ $purchaseRequisition->payment_schedule }}
                                    @else
                                        <span class="text-muted">ไม่ระบุ</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Committee Members -->
                    <div class="row mb-3">
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
                    
                    @if($purchaseRequisition->status === 'rejected' && $purchaseRequisition->rejection_reason)
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle me-1"></i> เหตุผลที่ไม่อนุมัติ:</h6>
                        <p class="mb-0">{{ $purchaseRequisition->rejection_reason }}</p>
                    </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">รหัสงบประมาณ</label>
                                <div>{{ $purchaseRequisition->budget_code ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">หมายเหตุ</label>
                                <div>{{ $purchaseRequisition->notes ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold fs-5">มูลค่ารวม: {{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency }}</span>
                        </div>
                        <div>
                            @if($purchaseRequisition->status === 'pending_approval' && auth()->user()->hasPermission('purchase_requisition.approve'))
                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fas fa-check-circle me-1"></i> อนุมัติ
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-times-circle me-1"></i> ไม่อนุมัติ
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- PR Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-1"></i> รายการสินค้า
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="35%">รายการ</th>
                                    <th width="10%">จำนวน</th>
                                    <th width="10%">หน่วย</th>
                                    <th width="15%">ราคาต่อหน่วย</th>
                                    <th width="15%">ราคารวม</th>
                                    <th width="10%">หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseRequisition->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>{{ $item->description }}</div>
                                        @if($item->specification)
                                        <small class="text-muted">
                                            <strong>รายละเอียดทางเทคนิค:</strong> {{ $item->specification }}
                                        </small>
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->quantity, 0) }}</td>
                                    <td>{{ $item->unit_of_measure ?? $item->unit ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item->estimated_unit_price ?? $item->unit_price ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->estimated_amount ?? $item->total_price ?? 0, 2) }}</td>
                                    <td>{{ $item->remarks ?? $item->notes ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">ไม่พบรายการสินค้า</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">รวม:</td>
                                    <td class="text-end fw-bold">{{ number_format($purchaseRequisition->total_amount, 2) }} {{ $purchaseRequisition->currency }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Attachments -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-paperclip me-1"></i> เอกสารแนบ
                    </h5>
                </div>
                <div class="card-body">
                    @if($purchaseRequisition->attachments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">ชื่อไฟล์</th>
                                    <th width="35%">คำอธิบาย</th>
                                    <th width="10%">ขนาด</th>
                                    <th width="10%">อัพโหลดโดย</th>
                                    <th width="10%">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseRequisition->attachments as $index => $attachment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $attachment->original_name }}</td>
                                    <td>{{ $attachment->description ?? '-' }}</td>
                                    <td>{{ $attachment->getFormattedFileSizeAttribute() }}</td>
                                    <td>{{ $attachment->uploader->name }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewFile({{ $attachment->id }}, '{{ addslashes($attachment->original_name) }}')" 
                                                    title="ดูไฟล์">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="{{ route('purchase-requisitions.download-attachment', $attachment) }}" 
                                               class="btn btn-sm btn-outline-secondary" title="ดาวน์โหลด">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center py-3 text-muted">ไม่มีเอกสารแนบ</p>
                    @endif
                </div>
            </div>

        </div>
        
        <div class="col-md-4">
            <!-- PR Timeline/Approval Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-1"></i> ประวัติการอนุมัติ
                    </h5>
                </div>
                <div class="card-body">
                    @if($purchaseRequisition->approvals->count() > 0)
                    <div class="timeline">
                        @foreach($purchaseRequisition->approvals->sortBy('approvalLevel.level') as $approval)
                        <div class="timeline-item">
                            <div class="timeline-marker 
                                @if($approval->status === 'approved') bg-success
                                @elseif($approval->status === 'rejected') bg-danger
                                @else bg-warning @endif">
                            </div>
                            <div class="timeline-content pb-4">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">{{ $approval->approvalLevel->name }}</h6>
                                    <div>
                                        @if($approval->status === 'approved')
                                        <span class="badge bg-success">อนุมัติแล้ว</span>
                                        @elseif($approval->status === 'rejected')
                                        <span class="badge bg-danger">ไม่อนุมัติ</span>
                                        @else
                                        <span class="badge bg-warning text-dark">รออนุมัติ</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-0">ผู้อนุมัติ: {{ $approval->user->name }}</p>
                                
                                @if($approval->status === 'approved')
                                <small class="text-muted">อนุมัติเมื่อ: {{ $approval->approved_at->format('d/m/Y H:i') }}</small>
                                @elseif($approval->status === 'rejected')
                                <small class="text-muted">ปฏิเสธเมื่อ: {{ $approval->rejected_at->format('d/m/Y H:i') }}</small>
                                @endif
                                
                                @if($approval->comments)
                                <div class="mt-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-2 px-3">
                                            <small>{{ $approval->comments }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-center py-3 text-muted">
                        @if($purchaseRequisition->status === 'draft')
                        ใบขอซื้อนี้ยังอยู่ในสถานะแบบร่าง
                        @elseif($purchaseRequisition->status === 'pending')
                        ใบขอซื้อนี้อยู่ระหว่างรอการอนุมัติ
                        @else
                        ไม่มีข้อมูลการอนุมัติ
                        @endif
                    </p>
                    @endif
                </div>
            </div>
            
            <!-- PR Activity Log -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-1"></i> กิจกรรมล่าสุด
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content pb-4">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">สร้างใบขอซื้อ</h6>
                                </div>
                                <p class="mb-0">โดย: {{ $purchaseRequisition->requester->name }}</p>
                                <small class="text-muted">เมื่อ: {{ $purchaseRequisition->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        @if($purchaseRequisition->created_at->format('Y-m-d H:i:s') !== $purchaseRequisition->updated_at->format('Y-m-d H:i:s'))
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content pb-4">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">อัปเดตใบขอซื้อล่าสุด</h6>
                                </div>
                                <small class="text-muted">เมื่อ: {{ $purchaseRequisition->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                        
                        <!-- สามารถเพิ่มกิจกรรมอื่นๆ เช่น การอนุมัติ การปฏิเสธ ได้ตามต้องการ -->
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<!-- Delete Modal -->
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
                <div class="row mb-2">
                    <div class="col-4"><strong>เลขที่:</strong></div>
                    <div class="col-8">{{ $purchaseRequisition->pr_number }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>หัวข้อ:</strong></div>
                    <div class="col-8">{{ $purchaseRequisition->title }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>สถานะ:</strong></div>
                    <div class="col-8">
                        @if($purchaseRequisition->status == 'draft')
                            <span class="badge bg-secondary">ร่าง</span>
                        @elseif($purchaseRequisition->status == 'rejected')
                            <span class="badge bg-danger">ถูกปฏิเสธ</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>จำนวนเงิน:</strong></div>
                    <div class="col-8">{{ number_format($purchaseRequisition->total_amount, 2) }} บาท</div>
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
                <form action="{{ route('purchase-requisitions.destroy', $purchaseRequisition) }}" method="POST">
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="fas fa-check-circle me-2"></i> ยืนยันการอนุมัติ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-requisitions.approve', $purchaseRequisition) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>คุณต้องการอนุมัติใบขอซื้อเลขที่ <strong>{{ $purchaseRequisition->pr_number }}</strong> ใช่หรือไม่?</p>
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">ความคิดเห็น (ไม่บังคับ)</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> อนุมัติ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="fas fa-times-circle me-2"></i> ยืนยันการไม่อนุมัติ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-requisitions.reject', $purchaseRequisition) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i> การไม่อนุมัติจะส่งใบขอซื้อกลับไปยังผู้ขอ
                    </div>
                    
                    <p>คุณต้องการไม่อนุมัติใบขอซื้อเลขที่ <strong>{{ $purchaseRequisition->pr_number }}</strong> ใช่หรือไม่?</p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">เหตุผลที่ไม่อนุมัติ <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                        @error('rejection_reason')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> ไม่อนุมัติ
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

<style>
/* Timeline */
.timeline {
    position: relative;
    padding-left: 1.5rem;
    margin: 0 0 0 1rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    left: -1.5rem;
    top: 0;
    border: 2px solid white;
}

.timeline-content {
    padding-bottom: 1.5rem;
}
</style>

@push('scripts')
<script>
    function submitForApproval(prId) {
        if (!confirm('คุณต้องการส่งใบ PR นี้ขออนุมัติหรือไม่?')) {
            return;
        }

        const submitBtn = event.target;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังส่ง...';

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
                document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.container-fluid').firstChild);
                
                // Reload page after 1.5 seconds
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> ส่งขออนุมัติ';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> ส่งขออนุมัติ';
        });
    }

    function viewFile(fileId, fileName) {
        // Set modal title
        document.getElementById('fileViewerTitle').textContent = fileName;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('fileViewerModal'));
        modal.show();
        
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
            contentDiv.innerHTML = `
                <iframe src="/pr-attachments/${fileId}/view" 
                        style="width: 100%; height: 100%; border: none;"
                        title="${fileName}">
                </iframe>
            `;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
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

@endsection