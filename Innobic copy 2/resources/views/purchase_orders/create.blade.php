@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        สร้างใบ PO ใหม่
                        @if(isset($purchaseRequisition))
                            <small class="text-muted">(จากใบ PR: {{ $purchaseRequisition->pr_number }})</small>
                        @endif
                    </h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('purchase-orders.store') }}" method="POST" enctype="multipart/form-data" id="poForm">
                        @csrf
                        @if(isset($purchaseRequisition))
                            <input type="hidden" name="purchase_requisition_id" value="{{ $purchaseRequisition->id }}">
                        @endif
                        
                        <!-- ข้อมูลหลักของ PO -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>ข้อมูลหลักของ PO
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="po_number" class="form-label">เลขที่ PO</label>
                                    <input type="text" class="form-control" id="po_number" 
                                           value="{{ $nextPoNumber }}" readonly>
                                    <div class="form-text">เลข PO จะถูกสร้างอัตโนมัติ</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sap_po_number" class="form-label">PO จาก SAP</label>
                                    <input type="text" class="form-control @error('sap_po_number') is-invalid @enderror" 
                                           id="sap_po_number" name="sap_po_number" 
                                           value="{{ old('sap_po_number') }}" 
                                           placeholder="กรอกเลขที่ PO จากระบบ SAP (ถ้ามี)">
                                    @error('sap_po_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">สำหรับเลข PO ที่มาจากภายนอก/SAP</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="po_title" class="form-label">ชื่องาน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('po_title') is-invalid @enderror" 
                                           id="po_title" name="po_title" 
                                           value="{{ old('po_title', isset($purchaseRequisition) ? $purchaseRequisition->title : '') }}" required>
                                    @error('po_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="work_type" class="form-label">ประเภทของงาน <span class="text-danger">*</span></label>
                                    <select class="form-select @error('work_type') is-invalid @enderror" id="work_type" name="work_type" required>
                                        <option value="">เลือกประเภทงาน</option>
                                        <option value="buy" {{ old('work_type', isset($purchaseRequisition) ? $purchaseRequisition->work_type : '') == 'buy' ? 'selected' : '' }}>ซื้อ</option>
                                        <option value="hire" {{ old('work_type', isset($purchaseRequisition) ? $purchaseRequisition->work_type : '') == 'hire' ? 'selected' : '' }}>จ้าง</option>
                                        <option value="rent" {{ old('work_type', isset($purchaseRequisition) ? $purchaseRequisition->work_type : '') == 'rent' ? 'selected' : '' }}>เช่า</option>
                                    </select>
                                    @error('work_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="procurement_method" class="form-label">วิธีการจัดหา</label>
                                    <select class="form-select @error('procurement_method') is-invalid @enderror" id="procurement_method" name="procurement_method">
                                        <option value="">เลือกวิธีการจัดหา</option>
                                        <option value="agreement_price" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'agreement_price' ? 'selected' : '' }}>ตกลงราคา</option>
                                        <option value="invitation_bid" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'invitation_bid' ? 'selected' : '' }}>เชิญชวน</option>
                                        <option value="open_bid" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'open_bid' ? 'selected' : '' }}>ประกวดราคาอิเล็กทรอนิกส์ (e-bidding)</option>
                                        <option value="special_1" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'special_1' ? 'selected' : '' }}>วิธีพิเศษ (กรณีที่ 1)</option>
                                        <option value="special_2" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'special_2' ? 'selected' : '' }}>วิธีพิเศษ (กรณีที่ 2)</option>
                                        <option value="selection" {{ old('procurement_method', isset($purchaseRequisition) ? $purchaseRequisition->procurement_method : '') == 'selection' ? 'selected' : '' }}>คัดเลือก</option>
                                    </select>
                                    @error('procurement_method')
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
                                                    {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
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
                                           id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                                    @error('contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror" 
                                           id="contact_email" name="contact_email" value="{{ old('contact_email') }}" required>
                                    @error('contact_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลผู้ขายและราคา -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-store me-2"></i>ข้อมูลผู้ขายและราคา
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_name" class="form-label">จัดหาจาก (ชื่อผู้ขาย)</label>
                                    <input type="text" class="form-control @error('vendor_name') is-invalid @enderror" 
                                           id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}" readonly>
                                    @error('vendor_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">จะถูกเติมอัตโนมัติเมื่อเลือกผู้ขาย</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">วงเงินที่ตกลงซื้อหรือจ้าง <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('total_amount') is-invalid @enderror" 
                                               id="total_amount" name="total_amount" step="0.01" min="0"
                                               value="{{ old('total_amount', isset($purchaseRequisition) ? $purchaseRequisition->total_amount : '') }}" required>
                                        <select class="form-select @error('currency') is-invalid @enderror" name="currency" style="max-width: 100px;">
                                            <option value="THB" {{ old('currency', 'THB') == 'THB' ? 'selected' : '' }}>THB</option>
                                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                        </select>
                                    </div>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stamp_duty" class="form-label">อากรแสตมป์ (กรณีงานจ้าง)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('stamp_duty') is-invalid @enderror" 
                                               id="stamp_duty" name="stamp_duty" step="0.01" min="0" value="{{ old('stamp_duty') }}">
                                        <span class="input-group-text">บาท</span>
                                    </div>
                                    @error('stamp_duty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลการส่งมอบและการชำระเงิน -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-calendar-alt me-2"></i>ข้อมูลการส่งมอบและการชำระเงิน
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_schedule" class="form-label">งวดการส่งมอบ</label>
                                    <textarea class="form-control @error('delivery_schedule') is-invalid @enderror" 
                                              id="delivery_schedule" name="delivery_schedule" rows="3">{{ old('delivery_schedule', isset($purchaseRequisition) ? $purchaseRequisition->delivery_schedule : '') }}</textarea>
                                    @error('delivery_schedule')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_schedule" class="form-label">งวดการจ่ายเงิน</label>
                                    <textarea class="form-control @error('payment_schedule') is-invalid @enderror" 
                                              id="payment_schedule" name="payment_schedule" rows="3">{{ old('payment_schedule', isset($purchaseRequisition) ? $purchaseRequisition->payment_schedule : '') }}</textarea>
                                    @error('payment_schedule')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_terms" class="form-label">เงื่อนไขในการชำระเงิน</label>
                                    <textarea class="form-control @error('payment_terms') is-invalid @enderror" 
                                              id="payment_terms" name="payment_terms" rows="3">{{ old('payment_terms') }}</textarea>
                                    @error('payment_terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="operation_duration" class="form-label">ระยะเวลาดำเนินการ</label>
                                    <textarea class="form-control @error('operation_duration') is-invalid @enderror" 
                                              id="operation_duration" name="operation_duration" rows="3">{{ old('operation_duration') }}</textarea>
                                    @error('operation_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- วันที่และหมายเหตุ -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-calendar me-2"></i>วันที่และหมายเหตุ
                                </h5>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="order_date" class="form-label">วันที่สั่ง <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('order_date') is-invalid @enderror" 
                                           id="order_date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required>
                                    @error('order_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expected_delivery_date" class="form-label">วันที่คาดว่าจะได้รับ</label>
                                    <input type="date" class="form-control @error('expected_delivery_date') is-invalid @enderror" 
                                           id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}">
                                    @error('expected_delivery_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">ความสำคัญ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        <option value="">เลือกความสำคัญ</option>
                                        <option value="low" {{ old('priority', isset($purchaseRequisition) ? $purchaseRequisition->priority : '') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority', isset($purchaseRequisition) ? $purchaseRequisition->priority : '') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="high" {{ old('priority', isset($purchaseRequisition) ? $purchaseRequisition->priority : '') == 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority', isset($purchaseRequisition) ? $purchaseRequisition->priority : '') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">หมายเหตุ</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- เอกสารแนบ -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-paperclip me-2"></i>เอกสารแนบ <span class="text-danger">*</span>
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <div id="file-upload-container">
                                    <div class="file-upload-item mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">ไฟล์เอกสาร</label>
                                                <input type="file" class="form-control @error('po_files.0') is-invalid @enderror" 
                                                       name="po_files[]" accept=".pdf,.jpg,.jpeg,.png" required>
                                                @error('po_files.0')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">คำอธิบาย</label>
                                                <input type="text" class="form-control" name="file_descriptions[]" 
                                                       placeholder="คำอธิบายไฟล์">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">ประเภท</label>
                                                <select class="form-select" name="file_categories[]" required>
                                                    <option value="po_document">เอกสาร PO</option>
                                                    <option value="quotation">ใบเสนอราคา</option>
                                                    <option value="specification">รายละเอียดทางเทคนิค</option>
                                                    <option value="attachment">เอกสารแนบ</option>
                                                    <option value="other">อื่นๆ</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger remove-file" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary" id="add-file">
                                    <i class="fas fa-plus me-1"></i> เพิ่มไฟล์
                                </button>
                            </div>
                        </div>

                        <!-- ปุ่มดำเนินการ -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> ยกเลิก
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> บันทึกใบ PO
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
    let fileIndex = 1;
    
    // เพิ่มไฟล์
    document.getElementById('add-file').addEventListener('click', function() {
        const container = document.getElementById('file-upload-container');
        const newItem = document.querySelector('.file-upload-item').cloneNode(true);
        
        // อัพเดทชื่อฟิลด์
        newItem.querySelector('input[type="file"]').name = `po_files[${fileIndex}]`;
        newItem.querySelector('input[type="file"]').value = '';
        newItem.querySelector('input[type="text"]').name = `file_descriptions[${fileIndex}]`;
        newItem.querySelector('input[type="text"]').value = '';
        newItem.querySelector('select').name = `file_categories[${fileIndex}]`;
        newItem.querySelector('select').selectedIndex = 0;
        
        // แสดงปุ่มลบ
        newItem.querySelector('.remove-file').style.display = 'block';
        
        container.appendChild(newItem);
        fileIndex++;
        
        updateRemoveButtons();
    });
    
    // ลบไฟล์
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-file')) {
            e.target.closest('.file-upload-item').remove();
            updateRemoveButtons();
        }
    });
    
    function updateRemoveButtons() {
        const items = document.querySelectorAll('.file-upload-item');
        items.forEach((item, index) => {
            const removeBtn = item.querySelector('.remove-file');
            if (items.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    // ฟังก์ชันเติมข้อมูลผู้ขายอัตโนมัติ
    window.fillVendorData = function() {
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
});
</script>
@endsection 