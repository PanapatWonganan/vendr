@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-lg-flex">
                        <div>
                            <h5 class="mb-0">ลงทะเบียนผู้ขาย</h5>
                            <p class="text-sm mb-0">
                                กรอกข้อมูลสำหรับลงทะเบียนผู้ขายใหม่
                            </p>
                        </div>
                        <div class="ms-auto my-auto mt-lg-0 mt-4">
                            <div class="ms-auto my-auto">
                                <a href="{{ route('vendors.index') }}" class="btn bg-gradient-secondary btn-sm mb-0">
                                    <i class="fas fa-arrow-left"></i>&nbsp;&nbsp;กลับ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vendors.store') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- ข้อมูลทั่วไป -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">ข้อมูลทั่วไป</h6>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name" class="form-control-label">ชื่อบริษัท <span class="text-danger">*</span></label>
                                    <input class="form-control @error('company_name') is-invalid @enderror" 
                                           type="text" 
                                           id="company_name" 
                                           name="company_name" 
                                           value="{{ old('company_name') }}"
                                           placeholder="ชื่อบริษัท">
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_id" class="form-control-label">เลขที่ Tax ID <span class="text-danger">*</span></label>
                                    <input class="form-control @error('tax_id') is-invalid @enderror" 
                                           type="text" 
                                           id="tax_id" 
                                           name="tax_id" 
                                           value="{{ old('tax_id') }}"
                                           placeholder="เลขที่ Tax ID">
                                    @error('tax_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="work_category" class="form-control-label">ประเภทของงาน <span class="text-danger">*</span></label>
                                    <select class="form-control @error('work_category') is-invalid @enderror" 
                                            id="work_category" 
                                            name="work_category">
                                        <option value="">เลือกประเภทของงาน</option>
                                        @foreach ($workCategories as $key => $value)
                                            <option value="{{ $key }}" {{ old('work_category') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('work_category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="documents" class="form-control-label">เอกสารแนบ (ประสบการณ์การทำงาน)</label>
                                    <input class="form-control @error('documents.*') is-invalid @enderror" 
                                           type="file" 
                                           id="documents" 
                                           name="documents[]"
                                           multiple
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <small class="form-text text-muted">
                                        รองรับไฟล์: PDF, DOC, DOCX, JPG, JPEG, PNG (ขนาดไม่เกิน 10MB)
                                    </small>
                                    @error('documents.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="form-control-label">ที่อยู่ตามหนังสือรับรอง <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" 
                                              id="address" 
                                              name="address" 
                                              rows="3"
                                              placeholder="ที่อยู่ตามหนังสือรับรอง">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="experience" class="form-control-label">ประสบการณ์การทำงาน</label>
                                    <textarea class="form-control @error('experience') is-invalid @enderror" 
                                              id="experience" 
                                              name="experience" 
                                              rows="4"
                                              placeholder="รายละเอียดประสบการณ์การทำงาน">{{ old('experience') }}</textarea>
                                    @error('experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr class="horizontal dark">
                        
                        <!-- ข้อมูล Point Person -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">ข้อมูลผู้ติดต่อประสานงาน</h6>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_name" class="form-control-label">ชื่อผู้ติดต่อ <span class="text-danger">*</span></label>
                                    <input class="form-control @error('contact_name') is-invalid @enderror" 
                                           type="text" 
                                           id="contact_name" 
                                           name="contact_name" 
                                           value="{{ old('contact_name') }}"
                                           placeholder="ชื่อผู้ติดต่อ">
                                    @error('contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_phone" class="form-control-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                    <input class="form-control @error('contact_phone') is-invalid @enderror" 
                                           type="tel" 
                                           id="contact_phone" 
                                           name="contact_phone" 
                                           value="{{ old('contact_phone') }}"
                                           placeholder="เบอร์โทรศัพท์">
                                    @error('contact_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_email" class="form-control-label">อีเมล <span class="text-danger">*</span></label>
                                    <input class="form-control @error('contact_email') is-invalid @enderror" 
                                           type="email" 
                                           id="contact_email" 
                                           name="contact_email" 
                                           value="{{ old('contact_email') }}"
                                           placeholder="อีเมล">
                                    @error('contact_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr class="horizontal dark">
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('vendors.index') }}" class="btn btn-light me-2">ยกเลิก</a>
                                    <button type="submit" class="btn bg-gradient-primary">ลงทะเบียน</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// File input preview
document.getElementById('documents').addEventListener('change', function(e) {
    const files = e.target.files;
    const fileList = document.getElementById('file-list');
    
    if (fileList) {
        fileList.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            const fileItem = document.createElement('div');
            fileItem.className = 'alert alert-info alert-dismissible';
            fileItem.innerHTML = `
                <span class="text-sm">
                    <i class="fas fa-file me-2"></i>
                    ${file.name} (${fileSize} MB)
                </span>
            `;
            
            fileList.appendChild(fileItem);
        }
    }
});
</script>
@endpush 