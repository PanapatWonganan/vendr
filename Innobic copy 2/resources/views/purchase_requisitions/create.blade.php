@extends('layouts.app')

@section('title', 'สร้างใบขอซื้อใหม่')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus-circle me-2"></i>สร้างใบขอซื้อใหม่
                    </h6>
                    <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> กลับไปยังรายการ
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchase-requisitions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- ข้อมูลทั่วไป -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pr_number" class="form-label">เลขที่ใบขอซื้อ</label>
                                    <input type="text" class="form-control" id="pr_number" value="{{ $nextPRNumber ?? 'PR'.date('Ymd').'-XXXX' }}" readonly>
                                    <small class="text-muted">เลขที่ใบขอซื้อจะถูกสร้างโดยอัตโนมัติหลังจากบันทึก</small>
                                </div>
                                <div class="mb-3">
                                    <label for="request_date" class="form-label">วันที่ขอซื้อ</label>
                                    <input type="date" class="form-control" id="request_date" value="{{ date('Y-m-d') }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="required_date" class="form-label">วันที่ต้องการ <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('required_date') is-invalid @enderror" 
                                           id="required_date" name="required_date" value="{{ old('required_date', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                    @error('required_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                                    <select class="form-select @error('department_id') is-invalid @enderror" 
                                            id="department_id" name="department_id" required>
                                        <option value="">-- เลือกแผนกก่อน --</option>
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
                                <div class="mb-3">
                                    <label for="requester_id" class="form-label">ผู้ขอซื้อ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('requester_id') is-invalid @enderror" 
                                            id="requester_id" name="requester_id" required disabled>
                                        <option value="">-- เลือกแผนกก่อน --</option>
                                    </select>
                                    @error('requester_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">เลือกพนักงานที่เป็นผู้ขอซื้อจริง (จากใบกระดาษ)</small>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">สถานะเริ่มต้น</label>
                                    <div>
                                        <span class="badge bg-secondary">แบบร่าง</span>
                                        <small class="text-muted ms-2">สถานะเริ่มต้นจะเป็นแบบร่างจนกว่าจะส่งเพื่อขออนุมัติ</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ชื่อใบขอซื้อ -->
                        <div class="mb-3">
                            <label for="title" class="form-label">ชื่อใบขอซื้อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title') }}" placeholder="ระบุชื่อหรือหัวข้อของใบขอซื้อ" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- หมวดหมู่ -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                <select id="category" name="category" class="form-select" required>
                                    <option value="">เลือกหมวดหมู่</option>
                                    @foreach(\App\Models\PurchaseRequisition::getCategoryOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="work_type" class="form-label">ประเภทของงาน <span class="text-danger">*</span></label>
                                <select id="work_type" name="work_type" class="form-select" required>
                                    <option value="">เลือกประเภทของงาน</option>
                                    @foreach(\App\Models\PurchaseRequisition::getWorkTypeOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('work_type') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('work_type')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- วิธีการจัดหา -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="procurement_method" class="form-label">วิธีการจัดหา</label>
                                <select class="form-select" id="procurement_method" name="procurement_method">
                                    <option value="">เลือกวิธีการจัดหา...</option>
                                    @foreach(\App\Models\PurchaseRequisition::getProcurementMethodOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('procurement_method') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('procurement_method')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- วงเงินและงวด -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="procurement_budget" class="form-label">วงเงินในการจัดหา</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="procurement_budget" name="procurement_budget" 
                                           value="{{ old('procurement_budget') }}" min="0" step="0.01" placeholder="0.00">
                                    <span class="input-group-text">บาท</span>
                                </div>
                                @error('procurement_budget')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="delivery_schedule" class="form-label">งวดการส่งมอบ</label>
                                <textarea class="form-control" id="delivery_schedule" name="delivery_schedule" 
                                          placeholder="ระบุงวดการส่งมอบ...">{{ old('delivery_schedule') }}</textarea>
                                @error('delivery_schedule')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="payment_schedule" class="form-label">งวดการจ่ายเงิน</label>
                                <textarea class="form-control" id="payment_schedule" name="payment_schedule" 
                                          placeholder="ระบุงวดการจ่ายเงิน...">{{ old('payment_schedule') }}</textarea>
                                @error('payment_schedule')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- ผู้รับผิดชอบ -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="procurement_committee_id" class="form-label">คณะกรรมการจัดหาพัสดุ</label>
                                <select class="form-select" id="procurement_committee_id" name="procurement_committee_id">
                                    <option value="">เลือกคณะกรรมการจัดหาพัสดุ...</option>
                                    @foreach($procurementCommitteeUsers as $user)
                                        <option value="{{ $user->id }}" {{ old('procurement_committee_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('procurement_committee_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="inspection_committee_id" class="form-label">คณะกรรมการตรวจรับ</label>
                                <select class="form-select" id="inspection_committee_id" name="inspection_committee_id">
                                    <option value="">เลือกคณะกรรมการตรวจรับ...</option>
                                    @foreach($inspectionCommitteeUsers as $user)
                                        <option value="{{ $user->id }}" {{ old('inspection_committee_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('inspection_committee_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="pr_approver_id" class="form-label">ผู้อนุมัติ PR</label>
                                <select class="form-select" id="pr_approver_id" name="pr_approver_id">
                                    <option value="">เลือกผู้อนุมัติ PR...</option>
                                    @foreach($approverUsers as $user)
                                        <option value="{{ $user->id }}" {{ old('pr_approver_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pr_approver_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="other_stakeholder_id" class="form-label">ผู้เกี่ยวข้องอื่น</label>
                                <select class="form-select" id="other_stakeholder_id" name="other_stakeholder_id">
                                    <option value="">เลือกผู้เกี่ยวข้องอื่น...</option>
                                    @foreach($otherStakeholderUsers as $user)
                                        <option value="{{ $user->id }}" {{ old('other_stakeholder_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('other_stakeholder_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- เหตุผลที่ขอซื้อ -->
                        <div class="mb-3">
                            <label for="description" class="form-label">เหตุผลที่ขอซื้อ</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" placeholder="ระบุเหตุผลหรือวัตถุประสงค์ในการขอซื้อ">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- การตั้งค่าเพิ่มเติม -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">สกุลเงิน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('currency') is-invalid @enderror" 
                                           id="currency" name="currency" value="{{ old('currency', 'THB') }}" required>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">ความสำคัญ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- รายการสินค้า -->
                        <div class="mb-4">
                            <label class="form-label"><strong>รายการสินค้า</strong></label>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="30%">รายการ</th>
                                            <th width="10%">จำนวน</th>
                                            <th width="10%">หน่วย</th>
                                            <th width="15%">ราคาต่อหน่วย</th>
                                            <th width="15%">ราคารวม</th>
                                            <th width="15%">การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td class="item-number">1</td>
                                            <td>
                                                <input type="text" class="form-control item-description" 
                                                       name="items[0][description]" value="{{ old('items.0.description') }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="1" step="1" class="form-control item-quantity" 
                                                       name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}" required
                                                       placeholder="จำนวน">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-unit" 
                                                       name="items[0][unit]" value="{{ old('items.0.unit', 'ชิ้น') }}" required
                                                       placeholder="หน่วย">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-price" 
                                                       name="items[0][unit_price]" value="{{ old('items.0.unit_price', '') }}" required
                                                       placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-total" 
                                                       name="items[0][total_price]" value="{{ old('items.0.total_price', 0) }}" readonly
                                                       style="background-color: #f8f9fa;">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm delete-row">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7">
                                                <button type="button" id="add-row" class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> เพิ่มรายการ
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="table-warning">
                                            <td colspan="5" class="text-end fw-bold fs-5">รวมทั้งสิ้น (THB):</td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control fw-bold fs-5" 
                                                       id="grand-total" name="total_amount" value="{{ old('total_amount', 0) }}" 
                                                       readonly style="background-color: #fff3cd; color: #856404;">
                                            </td>
                                            <td class="text-muted">
                                                <small>บาท</small>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- หมายเหตุ -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="ระบุข้อมูลเพิ่มเติมหรือหมายเหตุ (ถ้ามี)">{{ old('notes') }}</textarea>
                        </div>

                        <!-- เอกสารแนบ -->
                        <div class="mb-4">
                            <label class="form-label"><strong>เอกสารแนบ</strong></label>
                            <div class="border rounded p-3">
                                <div id="file-upload-area" class="text-center p-4 border-2 border-dashed rounded">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-2">คลิกเพื่อเลือกไฟล์หรือลากไฟล์มาวางที่นี่</p>
                                    <p class="text-muted small">รองรับไฟล์: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (ขนาดไม่เกิน 10MB)</p>
                                    <input type="file" id="file-input" name="attachments[]" multiple 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" 
                                           class="d-none">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('file-input').click()">
                                        <i class="fas fa-plus me-1"></i> เลือกไฟล์
                                    </button>
                                </div>
                                
                                <!-- รายการไฟล์ที่เลือก -->
                                <div id="selected-files" class="mt-3" style="display: none;">
                                    <h6>ไฟล์ที่เลือก:</h6>
                                    <div id="file-list"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                                <i class="fas fa-times me-1"></i>ยกเลิก
                            </button>
                            <button type="submit" name="save_as_draft" value="1" class="btn btn-info me-2">
                                <i class="fas fa-save me-1"></i>บันทึกเป็นแบบร่าง
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>ส่งเพื่อขออนุมัติ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // ข้อมูล users ที่ส่งมาจาก Controller
    const usersData = @json($users);
    
    // Debug: แสดงข้อมูล users ใน console
    console.log('Users Data:', usersData);
    
    $(document).ready(function() {
        // Cascading dropdown: Department -> Requester
        $('#department_id').on('change', function() {
            const departmentId = $(this).val();
            const requesterSelect = $('#requester_id');
            
            console.log('Department changed to:', departmentId);
            
            // Clear และ disable requester dropdown
            requesterSelect.empty().prop('disabled', true);
            requesterSelect.append('<option value="">-- เลือกผู้ขอซื้อ --</option>');
            
            if (departmentId) {
                // Filter users by department
                const departmentUsers = usersData.filter(user => 
                    user.department && user.department.id == departmentId
                );
                
                console.log('Filtered users for department', departmentId, ':', departmentUsers);
                
                if (departmentUsers.length > 0) {
                    // เปิดใช้งาน dropdown และเพิ่มตัวเลือก
                    requesterSelect.prop('disabled', false);
                    
                    departmentUsers.forEach(user => {
                        const option = `<option value="${user.id}">${user.name}</option>`;
                        requesterSelect.append(option);
                        console.log('Added user option:', user.name);
                    });
                    
                    // เลือกค่าเดิมถ้ามี (สำหรับกรณี validation error)
                    const oldRequesterId = '{{ old("requester_id") }}';
                    if (oldRequesterId) {
                        requesterSelect.val(oldRequesterId);
                    }
                } else {
                    console.log('No users found for department:', departmentId);
                    requesterSelect.append('<option value="">-- ไม่มีพนักงานในแผนกนี้ --</option>');
                }
            }
        });
        
        // กรณีมีการ reload หน้า (validation error) ให้ trigger การเลือกแผนก
        @if(old('department_id'))
            $('#department_id').trigger('change');
        @endif
        
        // คำนวณราคารวมเมื่อมีการเปลี่ยนแปลงจำนวนหรือราคาต่อหน่วย (แบบ realtime)
        $(document).on('input', '.item-quantity, .item-price', function() {
            const row = $(this).closest('tr');
            calculateRowTotal(row);
            updateGrandTotal();
            
            // เพิ่ม visual feedback เมื่อคำนวณเสร็จ
            const totalField = row.find('.item-total');
            totalField.addClass('bg-success').addClass('text-white');
            setTimeout(() => {
                totalField.removeClass('bg-success').removeClass('text-white');
            }, 300);
        });
        
        // เพิ่มแถวใหม่
        $('#add-row').click(function() {
            let newRow = createNewRow();
            $('#items-table tbody').append(newRow);
            renumberRows();
            
            // คำนวณยอดรวมทันทีหลังจากเพิ่มแถวใหม่
            updateGrandTotal();
            
            // Focus ไปที่ field รายการของแถวใหม่
            $('#items-table tbody tr:last .item-description').focus();
        });
        
        // ลบแถว
        $(document).on('click', '.delete-row', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('tr').remove();
                renumberRows();
                updateGrandTotal();
            } else {
                alert('ต้องมีอย่างน้อย 1 รายการ');
            }
        });
        
        // คำนวณราคารวมทั้งหมดเมื่อโหลดหน้า
        $('.item-row').each(function() {
            calculateRowTotal($(this));
        });
        updateGrandTotal();

        // อัพเดต event listener เพื่อรองรับการเปลี่ยนแปลงราคารวมด้วยตนเอง
        $(document).on('input', '.item-total', function() {
            updateGrandTotal();
        });

        // File upload handling
        const fileInput = document.getElementById('file-input');
        const fileUploadArea = document.getElementById('file-upload-area');
        const selectedFilesDiv = document.getElementById('selected-files');
        const fileList = document.getElementById('file-list');
        let selectedFiles = [];

        // File input change event
        fileInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop events
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.add('border-primary', 'bg-light');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.remove('border-primary', 'bg-light');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.classList.remove('border-primary', 'bg-light');
            handleFiles(e.dataTransfer.files);
        });
    });
    
    // สร้างแถวใหม่
    function createNewRow() {
        let rowCount = $('.item-row').length;
        let newRowIndex = rowCount;
        
        return `
            <tr class="item-row">
                <td class="item-number">${newRowIndex + 1}</td>
                <td>
                    <input type="text" class="form-control item-description" 
                           name="items[${newRowIndex}][description]" required>
                </td>
                <td>
                    <input type="number" min="1" step="1" class="form-control item-quantity" 
                           name="items[${newRowIndex}][quantity]" value="1" required
                           placeholder="จำนวน">
                </td>
                <td>
                    <input type="text" class="form-control item-unit" 
                           name="items[${newRowIndex}][unit]" value="ชิ้น" required
                           placeholder="หน่วย">
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control item-price" 
                           name="items[${newRowIndex}][unit_price]" value="" required
                           placeholder="0.00">
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control item-total" 
                           name="items[${newRowIndex}][total_price]" value="0.00" readonly
                           style="background-color: #f8f9fa;">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm delete-row">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                </td>
            </tr>
        `;
    }
    
    // คำนวณราคารวมของแต่ละแถว
    function calculateRowTotal(row) {
        let quantity = parseFloat(row.find('.item-quantity').val()) || 0;
        let price = parseFloat(row.find('.item-price').val()) || 0;
        let total = quantity * price;
        
        // อัพเดทค่าในฟิลด์ราคารวม
        row.find('.item-total').val(total.toFixed(2));
        
        // แสดงข้อมูลสำหรับ debug
        console.log(`Row calculation: ${quantity} × ${price} = ${total.toFixed(2)}`);
    }
    
    // อัพเดทลำดับเลขแถว
    function renumberRows() {
        $('.item-row').each(function(index) {
            $(this).find('.item-number').text(index + 1);
            
            // อัพเดท name attributes ให้ index ถูกต้อง
            $(this).find('.item-description').attr('name', `items[${index}][description]`);
            $(this).find('.item-quantity').attr('name', `items[${index}][quantity]`);
            $(this).find('.item-unit').attr('name', `items[${index}][unit]`);
            $(this).find('.item-price').attr('name', `items[${index}][unit_price]`);
            $(this).find('.item-total').attr('name', `items[${index}][total_price]`);
        });
    }
    
    // คำนวณราคารวมทั้งหมด
    function updateGrandTotal() {
        let grandTotal = 0;
        $('.item-total').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        
        $('#grand-total').val(grandTotal.toFixed(2));
        
        // เพิ่ม visual feedback สำหรับยอดรวม
        const grandTotalField = $('#grand-total');
        grandTotalField.addClass('bg-primary').addClass('text-white');
        setTimeout(() => {
            grandTotalField.removeClass('bg-primary').removeClass('text-white');
        }, 500);
        
        // แสดงข้อมูลสำหรับ debug
        console.log(`Grand Total: ${grandTotal.toFixed(2)} THB`);
    }

    // Handle file selection
    function handleFiles(files) {
        const maxFileSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png'
        ];

        Array.from(files).forEach(file => {
            // Check file size
            if (file.size > maxFileSize) {
                alert(`ไฟล์ "${file.name}" มีขนาดใหญ่เกิน 10MB`);
                return;
            }

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                alert(`ไฟล์ "${file.name}" ไม่รองรับประเภทไฟล์นี้`);
                return;
            }

            // Check if file already selected
            if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                alert(`ไฟล์ "${file.name}" ถูกเลือกแล้ว`);
                return;
            }

            selectedFiles.push(file);
        });

        updateFileList();
        updateFileInput();
    }

    // Update file list display
    function updateFileList() {
        if (selectedFiles.length === 0) {
            selectedFilesDiv.style.display = 'none';
            return;
        }

        selectedFilesDiv.style.display = 'block';
        fileList.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2';
            
            const fileIcon = getFileIcon(file.type);
            const fileSize = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="${fileIcon} me-2"></i>
                    <div>
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${fileSize}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            fileList.appendChild(fileItem);
        });
    }

    // Remove file from selection
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateFileList();
        updateFileInput();
    }

    // Update hidden file input
    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => {
            dt.items.add(file);
        });
        fileInput.files = dt.files;
    }

    // Get file icon based on file type
    function getFileIcon(fileType) {
        if (fileType.includes('pdf')) return 'fas fa-file-pdf text-danger';
        if (fileType.includes('word')) return 'fas fa-file-word text-primary';
        if (fileType.includes('excel') || fileType.includes('sheet')) return 'fas fa-file-excel text-success';
        if (fileType.includes('image')) return 'fas fa-file-image text-info';
        return 'fas fa-file text-secondary';
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>
@endpush