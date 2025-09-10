@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        แก้ไขใบ PO: {{ $po->po_number }}
                    </h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('purchase-orders.update', $po) }}" method="POST" enctype="multipart/form-data" id="poForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- PO Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>ข้อมูลใบ PO
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="po_number" class="form-label">เลข PO</label>
                                    <input type="text" class="form-control" id="po_number" 
                                           value="{{ $po->po_number }}" readonly>
                                    <div class="form-text">เลข PO ไม่สามารถแก้ไขได้</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sap_po_number" class="form-label">PO จาก SAP</label>
                                    <input type="text" class="form-control @error('sap_po_number') is-invalid @enderror" 
                                           id="sap_po_number" name="sap_po_number" 
                                           value="{{ old('sap_po_number', $po->sap_po_number) }}" 
                                           placeholder="กรอกเลขที่ PO จากระบบ SAP (ถ้ามี)">
                                    @error('sap_po_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">สำหรับเลข PO ที่มาจากภายนอก/SAP</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="po_title" class="form-label">ชื่อใบ PO <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('po_title') is-invalid @enderror" 
                                           id="po_title" name="po_title" value="{{ old('po_title', $po->po_title) }}" required>
                                    @error('po_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">รายละเอียด</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $po->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลบริษัทและผู้ติดต่อ -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-building me-2"></i>ข้อมูลบริษัทและผู้ติดต่อ
                                </h5>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">เลือกผู้ขาย <span class="text-danger">*</span></label>
                                    <select class="form-select @error('vendor_id') is-invalid @enderror" 
                                            id="vendor_id" name="vendor_id" required onchange="fillVendorData()">
                                        <option value="">เลือกผู้ขาย</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" 
                                                    data-company="{{ $vendor->company_name }}"
                                                    data-contact="{{ $vendor->contact_name }}"
                                                    data-email="{{ $vendor->contact_email }}"
                                                    {{ old('vendor_id', $po->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->company_name }} ({{ $vendor->contact_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">ชื่อผู้ติดต่อ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('contact_name') is-invalid @enderror" 
                                           id="contact_name" name="contact_name" value="{{ old('contact_name', $po->contact_name) }}" required>
                                    @error('contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror" 
                                           id="contact_email" name="contact_email" value="{{ old('contact_email', $po->contact_email) }}" required>
                                    @error('contact_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_name" class="form-label">จัดหาจาก (ชื่อผู้ขาย)</label>
                                    <input type="text" class="form-control @error('vendor_name') is-invalid @enderror" 
                                           id="vendor_name" name="vendor_name" value="{{ old('vendor_name', $po->vendor_name) }}" readonly>
                                    @error('vendor_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">จะถูกเติมอัตโนมัติเมื่อเลือกผู้ขาย</div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-clipboard-list me-2"></i>รายละเอียดการสั่งซื้อ
                                </h5>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">จำนวนเงิน <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_amount') is-invalid @enderror" 
                                           id="total_amount" name="total_amount" value="{{ old('total_amount', $po->total_amount) }}" 
                                           step="0.01" min="0" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">สกุลเงิน</label>
                                    <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                                        <option value="THB" {{ old('currency', $po->currency) == 'THB' ? 'selected' : '' }}>THB</option>
                                        <option value="USD" {{ old('currency', $po->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency', $po->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="order_date" class="form-label">วันที่สั่งซื้อ <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('order_date') is-invalid @enderror" 
                                           id="order_date" name="order_date" value="{{ old('order_date', $po->order_date?->format('Y-m-d')) }}" required>
                                    @error('order_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="expected_delivery_date" class="form-label">วันที่คาดว่าจะส่งมอบ</label>
                                    <input type="date" class="form-control @error('expected_delivery_date') is-invalid @enderror" 
                                           id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $po->expected_delivery_date?->format('Y-m-d')) }}">
                                    @error('expected_delivery_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">ระดับความสำคัญ</label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority">
                                        <option value="low" {{ old('priority', $po->priority) == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority', $po->priority) == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="high" {{ old('priority', $po->priority) == 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority', $po->priority) == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                        <option value="">เลือกแผนก</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id', $po->department_id) == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_address" class="form-label">ที่อยู่ส่งมอบ</label>
                                    <textarea class="form-control @error('delivery_address') is-invalid @enderror" 
                                              id="delivery_address" name="delivery_address" rows="2">{{ old('delivery_address', $po->delivery_address) }}</textarea>
                                    @error('delivery_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_terms" class="form-label">เงื่อนไขการชำระเงิน</label>
                                    <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" 
                                           id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $po->payment_terms) }}"
                                           placeholder="เช่น: 30 วัน, เงินสด, เครดิต">
                                    @error('payment_terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">หมายเหตุ</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3">{{ old('notes', $po->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Existing Files -->
                        @if($po->files->count() > 0)
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-paperclip me-2"></i>ไฟล์ที่มีอยู่
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ชื่อไฟล์</th>
                                                <th>หมวดหมู่</th>
                                                <th>ขนาด</th>
                                                <th>วันที่อัพโหลด</th>
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
                                                    <td>{{ $file->created_at ? $file->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('po-files.download', $file) }}" 
                                                               class="btn btn-outline-primary" title="ดาวน์โหลด">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="deleteFile({{ $file->id }})" title="ลบ">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Add New Files -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-upload me-2"></i>เพิ่มไฟล์ใหม่ (ไม่บังคับ)
                                </h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    คุณสามารถเพิ่มไฟล์เอกสารใหม่ได้ ในรูปแบบ PDF หรือ PNG/JPG ขนาดไม่เกิน 10MB ต่อไฟล์
                                </div>
                            </div>
                            
                            <div id="file-upload-container">
                                <div class="file-upload-row mb-3">
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">ไฟล์เอกสาร</label>
                                            <input type="file" class="form-control" name="po_files[]" accept=".pdf,.png,.jpg,.jpeg">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">หมวดหมู่ไฟล์</label>
                                            <select class="form-select" name="file_categories[]">
                                                <option value="po_document">เอกสาร PO</option>
                                                <option value="quotation">ใบเสนอราคา</option>
                                                <option value="specification">รายละเอียดสินค้า</option>
                                                <option value="attachment">เอกสารแนบ</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">คำอธิบายไฟล์</label>
                                            <input type="text" class="form-control" name="file_descriptions[]" 
                                                   placeholder="อธิบายเนื้อหาไฟล์ (ไม่บังคับ)">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-success w-100" onclick="addFileRow()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>ยกเลิก
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>บันทึกการแก้ไข
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Add new file upload row
function addFileRow() {
    const container = document.getElementById('file-upload-container');
    const newRow = document.createElement('div');
    newRow.className = 'file-upload-row mb-3';
    newRow.innerHTML = `
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">ไฟล์เอกสาร</label>
                <input type="file" class="form-control" name="po_files[]" accept=".pdf,.png,.jpg,.jpeg">
            </div>
            <div class="col-md-3">
                <label class="form-label">หมวดหมู่ไฟล์</label>
                <select class="form-select" name="file_categories[]">
                    <option value="po_document">เอกสาร PO</option>
                    <option value="quotation">ใบเสนอราคา</option>
                    <option value="specification">รายละเอียดสินค้า</option>
                    <option value="attachment">เอกสารแนบ</option>
                    <option value="other">อื่นๆ</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">คำอธิบายไฟล์</label>
                <input type="text" class="form-control" name="file_descriptions[]" 
                       placeholder="อธิบายเนื้อหาไฟล์ (ไม่บังคับ)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger w-100" onclick="removeFileRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

// Remove file upload row
function removeFileRow(button) {
    button.closest('.file-upload-row').remove();
}

// Date validation
document.getElementById('order_date').addEventListener('change', function() {
    const orderDate = this.value;
    const deliveryDateInput = document.getElementById('expected_delivery_date');
    
    if (orderDate) {
        deliveryDateInput.min = orderDate;
        if (deliveryDateInput.value && deliveryDateInput.value < orderDate) {
            deliveryDateInput.value = '';
            alert('วันที่คาดว่าจะส่งมอบต้องไม่ก่อนวันที่สั่งซื้อ');
        }
    }
});

// Delete file function
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

// ฟังก์ชันเติมข้อมูลผู้ขายอัตโนมัติ
function fillVendorData() {
    const vendorSelect = document.getElementById('vendor_id');
    const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
    
    if (selectedOption.value) {
        // เติมข้อมูลอัตโนมัติ
        document.getElementById('vendor_name').value = selectedOption.dataset.company || '';
        document.getElementById('contact_name').value = selectedOption.dataset.contact || '';
        document.getElementById('contact_email').value = selectedOption.dataset.email || '';
    } else {
        // ล้างข้อมูลเมื่อไม่ได้เลือก
        document.getElementById('vendor_name').value = '';
        document.getElementById('contact_name').value = '';
        document.getElementById('contact_email').value = '';
    }
}
</script>
@endpush
@endsection 