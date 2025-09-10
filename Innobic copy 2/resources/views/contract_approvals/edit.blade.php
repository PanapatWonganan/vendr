@extends('layouts.app')

@section('title', 'แก้ไขสัญญา')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit me-2"></i>แก้ไขสัญญา #{{ $contract->contract_number }}
                    </h6>
                    <div>
                        <a href="{{ route('contract-approvals.show', $contract) }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> กลับไปดูรายละเอียด
                        </a>
                        <a href="{{ route('contract-approvals.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> รายการทั้งหมด
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('contract-approvals.update', $contract) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- ข้อมูลสัญญา -->
                        <div class="mb-3">
                            <label for="contract_title" class="form-label">ชื่อสัญญา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contract_title') is-invalid @enderror" 
                                   id="contract_title" name="contract_title" value="{{ old('contract_title', $contract->contract_title) }}" 
                                   placeholder="ระบุชื่อหรือหัวข้อของสัญญา" required>
                            @error('contract_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">รายละเอียดสัญญา</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="ระบุรายละเอียดหรือวัตถุประสงค์ของสัญญา">{{ old('description', $contract->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_name" class="form-label">ชื่อผู้ขาย/ผู้รับจ้าง <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('vendor_name') is-invalid @enderror" 
                                           id="vendor_name" name="vendor_name" value="{{ old('vendor_name', $contract->vendor_name) }}" 
                                           placeholder="ชื่อบริษัทหรือบุคคลที่ทำสัญญาด้วย" required>
                                    @error('vendor_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contract_value" class="form-label">มูลค่าสัญญา <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('contract_value') is-invalid @enderror" 
                                           id="contract_value" name="contract_value" value="{{ old('contract_value', $contract->contract_value) }}" required>
                                    @error('contract_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contract_date" class="form-label">วันที่ทำสัญญา <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('contract_date') is-invalid @enderror" 
                                           id="contract_date" name="contract_date" value="{{ old('contract_date', $contract->contract_date->format('Y-m-d')) }}" required>
                                    @error('contract_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">วันที่เริ่มต้น <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" value="{{ old('start_date', $contract->start_date->format('Y-m-d')) }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">วันที่สิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" value="{{ old('end_date', $contract->end_date->format('Y-m-d')) }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contract_type" class="form-label">ประเภทสัญญา <span class="text-danger">*</span></label>
                                    <select class="form-select @error('contract_type') is-invalid @enderror" id="contract_type" name="contract_type" required>
                                        <option value="">เลือกประเภทสัญญา</option>
                                        <option value="purchase" {{ old('contract_type', $contract->contract_type) == 'purchase' ? 'selected' : '' }}>จัดซื้อ</option>
                                        <option value="service" {{ old('contract_type', $contract->contract_type) == 'service' ? 'selected' : '' }}>จัดจ้าง</option>
                                        <option value="rental" {{ old('contract_type', $contract->contract_type) == 'rental' ? 'selected' : '' }}>เช่า</option>
                                        <option value="maintenance" {{ old('contract_type', $contract->contract_type) == 'maintenance' ? 'selected' : '' }}>บำรุงรักษา</option>
                                        <option value="other" {{ old('contract_type', $contract->contract_type) == 'other' ? 'selected' : '' }}>อื่นๆ</option>
                                    </select>
                                    @error('contract_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">สกุลเงิน <span class="text-danger">*</span></label>
                                    <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                                        <option value="THB" {{ old('currency', $contract->currency) == 'THB' ? 'selected' : '' }}>THB (บาท)</option>
                                        <option value="USD" {{ old('currency', $contract->currency) == 'USD' ? 'selected' : '' }}>USD (ดอลลาร์)</option>
                                        <option value="EUR" {{ old('currency', $contract->currency) == 'EUR' ? 'selected' : '' }}>EUR (ยูโร)</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                        <option value="">เลือกแผนก</option>
                                        @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $contract->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">ความสำคัญ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        <option value="">เลือกความสำคัญ</option>
                                        <option value="low" {{ old('priority', $contract->priority) == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority', $contract->priority) == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="high" {{ old('priority', $contract->priority) == 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority', $contract->priority) == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="project_code" class="form-label">รหัสโครงการ</label>
                                    <input type="text" class="form-control @error('project_code') is-invalid @enderror" 
                                           id="project_code" name="project_code" value="{{ old('project_code', $contract->project_code) }}" 
                                           placeholder="รหัสโครงการ (ถ้ามี)">
                                    @error('project_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="budget_code" class="form-label">รหัสงบประมาณ</label>
                                    <input type="text" class="form-control @error('budget_code') is-invalid @enderror" 
                                           id="budget_code" name="budget_code" value="{{ old('budget_code', $contract->budget_code) }}" 
                                           placeholder="รหัสงบประมาณ (ถ้ามี)">
                                    @error('budget_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- ไฟล์ที่มีอยู่ -->
                        @if($contract->files->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-file me-1"></i> ไฟล์ที่มีอยู่</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ชื่อไฟล์</th>
                                                <th>ประเภท</th>
                                                <th>ขนาด</th>
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
                                                <td>
                                                    <a href="{{ route('contract-files.download', $file) }}" 
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteFile({{ $file->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- อัพโหลดไฟล์ใหม่ -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-upload me-1"></i> อัพโหลดไฟล์เพิ่มเติม</h6>
                            </div>
                            <div class="card-body">
                                <small class="text-muted d-block mb-2">รองรับไฟล์: PDF, JPG, JPEG, PNG (ขนาดไม่เกิน 10MB ต่อไฟล์)</small>
                                
                                <div id="file-container">
                                    <div class="file-row mb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="file" class="form-control" name="contract_files[]" accept=".pdf,.jpg,.jpeg,.png">
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="file_categories[]">
                                                    <option value="contract">ไฟล์สัญญาหลัก</option>
                                                    <option value="attachment" selected>เอกสารแนบ</option>
                                                    <option value="amendment">เอกสารแก้ไขเพิ่มเติม</option>
                                                    <option value="approval">เอกสารอนุมัติ</option>
                                                    <option value="other">อื่นๆ</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="file_descriptions[]" 
                                                       placeholder="คำอธิบายไฟล์ (ไม่บังคับ)">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-outline-success add-file-btn" title="เพิ่มไฟล์">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">หมายเหตุ: หากไม่ต้องการอัพโหลดไฟล์เพิ่มเติม สามารถเว้นว่างได้</small>
                            </div>
                        </div>

                        <!-- ปุ่มบันทึก -->
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('contract-approvals.show', $contract) }}" class="btn btn-secondary me-2">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add file row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-file-btn')) {
            const container = document.getElementById('file-container');
            const newRow = document.createElement('div');
            newRow.className = 'file-row mb-3';
            newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <input type="file" class="form-control" name="contract_files[]" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="file_categories[]">
                            <option value="contract">ไฟล์สัญญาหลัก</option>
                            <option value="attachment" selected>เอกสารแนบ</option>
                            <option value="amendment">เอกสารแก้ไขเพิ่มเติม</option>
                            <option value="approval">เอกสารอนุมัติ</option>
                            <option value="other">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="file_descriptions[]" 
                               placeholder="คำอธิบายไฟล์ (ไม่บังคับ)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger remove-file-btn" title="ลบไฟล์">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }
    });

    // Remove file row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-file-btn')) {
            e.target.closest('.file-row').remove();
        }
    });

    // Date validation
    const contractDate = document.getElementById('contract_date');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    function validateDates() {
        if (contractDate.value && startDate.value) {
            if (new Date(startDate.value) < new Date(contractDate.value)) {
                startDate.setCustomValidity('วันที่เริ่มต้นต้องไม่ก่อนวันที่ทำสัญญา');
            } else {
                startDate.setCustomValidity('');
            }
        }

        if (startDate.value && endDate.value) {
            if (new Date(endDate.value) <= new Date(startDate.value)) {
                endDate.setCustomValidity('วันที่สิ้นสุดต้องหลังวันที่เริ่มต้น');
            } else {
                endDate.setCustomValidity('');
            }
        }
    }

    contractDate.addEventListener('change', validateDates);
    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);
});

function deleteFile(fileId) {
    if (confirm('คุณต้องการลบไฟล์นี้ใช่หรือไม่?')) {
        fetch(`/contract-files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
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
            alert('เกิดข้อผิดพลาดในการลบไฟล์');
        });
    }
}
</script>
@endsection
