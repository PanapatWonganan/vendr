@extends('layouts.app')

@section('title', 'รายละเอียดสัญญา')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-contract me-2"></i>รายละเอียดสัญญา #{{ $contract->contract_number }}
                    </h6>
                    <div>
                        <a href="{{ route('contract-approvals.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> กลับไปยังรายการ
                        </a>
                        @if(in_array($contract->status, ['pending', 'rejected']))
                        <a href="{{ route('contract-approvals.edit', $contract) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i> แก้ไข
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- สถานะและข้อมูลหลัก -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4>{{ $contract->contract_title }}</h4>
                            @if($contract->description)
                            <p class="text-muted">{{ $contract->description }}</p>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <span class="badge {{ $contract->status_badge }} fs-6">{{ $contract->status_text }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="badge {{ $contract->priority_badge }}">{{ $contract->priority_text }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลสัญญา -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i> ข้อมูลสัญญา</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>เลขที่สัญญา:</strong></td>
                                            <td>{{ $contract->contract_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>ชื่อผู้ขาย/ผู้รับจ้าง:</strong></td>
                                            <td>{{ $contract->vendor_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>ประเภทสัญญา:</strong></td>
                                            <td>{{ $contract->contract_type_text }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>มูลค่าสัญญา:</strong></td>
                                            <td><strong class="text-success">{{ number_format($contract->contract_value, 2) }} {{ $contract->currency }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>แผนก:</strong></td>
                                            <td>{{ $contract->department->name }}</td>
                                        </tr>
                                        @if($contract->project_code)
                                        <tr>
                                            <td><strong>รหัสโครงการ:</strong></td>
                                            <td>{{ $contract->project_code }}</td>
                                        </tr>
                                        @endif
                                        @if($contract->budget_code)
                                        <tr>
                                            <td><strong>รหัสงบประมาณ:</strong></td>
                                            <td>{{ $contract->budget_code }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-calendar me-1"></i> วันที่และระยะเวลา</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>วันที่ทำสัญญา:</strong></td>
                                            <td>{{ $contract->contract_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>วันที่เริ่มต้น:</strong></td>
                                            <td>{{ $contract->start_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>วันที่สิ้นสุด:</strong></td>
                                            <td>{{ $contract->end_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>ระยะเวลา:</strong></td>
                                            <td>{{ $contract->start_date->diffInDays($contract->end_date) }} วัน</td>
                                        </tr>
                                        <tr>
                                            <td><strong>วันที่อัพโหลด:</strong></td>
                                            <td>{{ $contract->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>ผู้อัพโหลด:</strong></td>
                                            <td>{{ $contract->uploader->name }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ไฟล์แนบ -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-file me-1"></i> ไฟล์แนบ</h6>
                        </div>
                        <div class="card-body">
                            @if($contract->files->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ชื่อไฟล์</th>
                                            <th>ประเภท</th>
                                            <th>ขนาด</th>
                                            <th>คำอธิบาย</th>
                                            <th>ผู้อัพโหลด</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contract->files as $file)
                                        <tr>
                                            <td>
                                                <i class="fas fa-{{ $file->isPdf() ? 'file-pdf' : 'file-image' }} me-1"></i>
                                                {{ $file->original_name }}
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $file->file_category_text }}</span>
                                            </td>
                                            <td>{{ $file->file_size_human }}</td>
                                            <td>{{ $file->description ?? '-' }}</td>
                                            <td>{{ $file->uploader->name }}</td>
                                            <td>
                                                <a href="{{ route('contract-files.download', $file) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-download me-1"></i> ดาวน์โหลด
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted">ไม่มีไฟล์แนบ</p>
                            @endif
                        </div>
                    </div>

                    <!-- ข้อมูลการตรวจสอบ -->
                    @if($contract->reviewed_by || $contract->review_notes || $contract->rejection_reason)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-clipboard-check me-1"></i> ข้อมูลการตรวจสอบ</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                @if($contract->reviewer)
                                <tr>
                                    <td width="20%"><strong>ผู้ตรวจสอบ:</strong></td>
                                    <td>{{ $contract->reviewer->name }}</td>
                                </tr>
                                @endif
                                @if($contract->reviewed_at)
                                <tr>
                                    <td><strong>วันที่ตรวจสอบ:</strong></td>
                                    <td>{{ $contract->reviewed_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endif
                                @if($contract->review_notes)
                                <tr>
                                    <td><strong>หมายเหตุ:</strong></td>
                                    <td>{{ $contract->review_notes }}</td>
                                </tr>
                                @endif
                                @if($contract->rejection_reason)
                                <tr>
                                    <td><strong>เหตุผลที่ไม่อนุมัติ:</strong></td>
                                    <td class="text-danger">{{ $contract->rejection_reason }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- ปุ่มดำเนินการ -->
                    @if(auth()->user()->roles->contains('name', 'admin'))
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-cogs me-1"></i> การดำเนินการ</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if($contract->status == 'pending')
                                <div class="col-md-4">
                                    <form action="{{ route('contract-approvals.start-review', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info w-100 mb-2">
                                            <i class="fas fa-search me-1"></i> เริ่มตรวจสอบ
                                        </button>
                                    </form>
                                </div>
                                @endif

                                @if(in_array($contract->status, ['pending', 'under_review']))
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-success w-100 mb-2" 
                                            data-bs-toggle="modal" data-bs-target="#approveModal">
                                        <i class="fas fa-check me-1"></i> อนุมัติ
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-danger w-100 mb-2" 
                                            data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="fas fa-times me-1"></i> ไม่อนุมัติ
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="fas fa-check me-2"></i> อนุมัติสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('contract-approvals.approve', $contract) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>คุณต้องการอนุมัติสัญญาเลขที่ <strong>{{ $contract->contract_number }}</strong> ใช่หรือไม่?</p>
                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">หมายเหตุ (ไม่บังคับ)</label>
                        <textarea class="form-control" id="approve_notes" name="review_notes" rows="3" 
                                  placeholder="เพิ่มหมายเหตุการอนุมัติ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> อนุมัติสัญญา
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
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="fas fa-times me-2"></i> ไม่อนุมัติสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('contract-approvals.reject', $contract) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i> การไม่อนุมัติจะส่งสัญญากลับไปยังผู้อัพโหลด
                    </div>
                    <p>คุณต้องการไม่อนุมัติสัญญาเลขที่ <strong>{{ $contract->contract_number }}</strong> ใช่หรือไม่?</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">เหตุผลที่ไม่อนุมัติ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" 
                                  placeholder="ระบุเหตุผลที่ไม่อนุมัติ..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-control" id="reject_notes" name="review_notes" rows="2" 
                                  placeholder="หมายเหตุเพิ่มเติม..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i> ไม่อนุมัติ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
