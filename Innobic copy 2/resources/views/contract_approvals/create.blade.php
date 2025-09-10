@extends('layouts.app')

@section('title', 'อัพโหลดสัญญาใหม่')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload me-2"></i>อัพโหลดสัญญาใหม่
                    </h6>
                    <a href="{{ route('contract-approvals.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> กลับไปยังรายการ
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('contract-approvals.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- ข้อมูลสัญญา -->
                        <div class="mb-3">
                            <label for="contract_title" class="form-label">ชื่อสัญญา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contract_title') is-invalid @enderror" 
                                   id="contract_title" name="contract_title" value="{{ old('contract_title') }}" 
                                   placeholder="ระบุชื่อหรือหัวข้อของสัญญา" required>
                            @error('contract_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_name" class="form-label">ชื่อผู้ขาย/ผู้รับจ้าง <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('vendor_name') is-invalid @enderror" 
                                           id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}" 
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
                                           id="contract_value" name="contract_value" value="{{ old('contract_value') }}" required>
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
                                           id="contract_date" name="contract_date" value="{{ old('contract_date', date('Y-m-d')) }}" required>
                                    @error('contract_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">วันที่เริ่มต้น <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">วันที่สิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" value="{{ old('end_date') }}" required>
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
                                        <option value="purchase" {{ old('contract_type') == 'purchase' ? 'selected' : '' }}>จัดซื้อ</option>
                                        <option value="service" {{ old('contract_type') == 'service' ? 'selected' : '' }}>จัดจ้าง</option>
                                        <option value="rental" {{ old('contract_type') == 'rental' ? 'selected' : '' }}>เช่า</option>
                                        <option value="maintenance" {{ old('contract_type') == 'maintenance' ? 'selected' : '' }}>บำรุงรักษา</option>
                                        <option value="other" {{ old('contract_type') == 'other' ? 'selected' : '' }}>อื่นๆ</option>
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
                                        <option value="THB" {{ old('currency', 'THB') == 'THB' ? 'selected' : '' }}>THB (บาท)</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD (ดอลลาร์)</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (ยูโร)</option>
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
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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

                        <div class="mb-3">
                            <label for="priority" class="form-label">ความสำคัญ <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                <option value="">เลือกความสำคัญ</option>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- อัพโหลดไฟล์ -->
                        <div class="mb-4">
                            <label class="form-label"><strong>ไฟล์สัญญา <span class="text-danger">*</span></strong></label>
                            <small class="text-muted d-block mb-2">รองรับไฟล์: PDF, JPG, JPEG, PNG (ขนาดไม่เกิน 10MB ต่อไฟล์)</small>
                            
                            <div class="mb-3">
                                <input type="file" class="form-control @error('contract_files.0') is-invalid @enderror" 
                                       name="contract_files[]" accept=".pdf,.jpg,.jpeg,.png" required>
                                @error('contract_files.0')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <select class="form-select" name="file_categories[]" required>
                                    <option value="contract" selected>ไฟล์สัญญาหลัก</option>
                                    <option value="attachment">เอกสารแนบ</option>
                                    <option value="amendment">เอกสารแก้ไขเพิ่มเติม</option>
                                    <option value="approval">เอกสารอนุมัติ</option>
                                    <option value="other">อื่นๆ</option>
                                </select>
                            </div>
                        </div>

                        <!-- ปุ่มบันทึก -->
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('contract-approvals.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> อัพโหลดสัญญา
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 