@extends('layouts.app')

@section('title', 'สร้างใบขอซื้อตรง ≤10,000 บาท')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-shopping-cart me-2"></i>สร้างใบขอซื้อตรง (ไม่เกิน 10,000 บาท)
                    </h6>
                    <div>
                        <span class="badge bg-warning text-dark me-2">
                            <i class="fas fa-exclamation-triangle"></i> ยอดรวมต้องไม่เกิน 10,000 บาท
                        </span>
                        <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i> กลับ
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>พบข้อผิดพลาด!</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <div class="mt-2">
                                <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> เพิ่มข้อมูลผู้ขาย
                                </a>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($vendors->isEmpty())
                        <div class="alert alert-danger" role="alert">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>ไม่สามารถสร้าง PR จัดซื้อตรงได้</h6>
                            <p class="mb-2">ไม่พบข้อมูลผู้ขายในบริษัทนี้ กรุณาเพิ่มข้อมูลผู้ขายก่อน</p>
                            <a href="{{ route('vendors.index') }}" class="btn btn-primary">
                                <i class="fas fa-store me-1"></i> ไปจัดการผู้ขาย
                            </a>
                        </div>
                    @endif

                    @if($vendors->isNotEmpty())
                    <form action="{{ route('purchase-requisitions.store-direct-small') }}" method="POST" id="directPurchaseForm">
                        @csrf
                        
                        <!-- ข้อมูลทั่วไป -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>ข้อมูลทั่วไป</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">เลขที่ใบขอซื้อ</label>
                                            <input type="text" class="form-control" value="{{ $prNumber }}" readonly>
                                            <small class="text-muted">สร้างอัตโนมัติ</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">วันที่ขออนุมัติ <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('approval_request_date') is-invalid @enderror" 
                                                   name="approval_request_date" value="{{ old('approval_request_date', date('Y-m-d')) }}" required>
                                            @error('approval_request_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ข้อ <span class="text-danger">*</span></label>
                                            <select class="form-select @error('clause_number') is-invalid @enderror" 
                                                    name="clause_number" id="clauseNumber" required>
                                                <option value="">-- เลือกข้อ --</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}" {{ old('clause_number') == $i ? 'selected' : '' }}>
                                                        ข้อ {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('clause_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">แสดงข้อที่เลือก</label>
                                            <div class="alert alert-info" id="clauseDisplay">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <span id="clauseText">กรุณาเลือกข้อ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- หมวดหมู่และประเภทของงาน -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                            <select class="form-select @error('category') is-invalid @enderror" name="category" required>
                                                <option value="">เลือกหมวดหมู่</option>
                                                @foreach(\App\Models\PurchaseRequisition::getCategoryOptions() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ประเภทของงาน <span class="text-danger">*</span></label>
                                            <select class="form-select @error('work_type') is-invalid @enderror" name="work_type" required>
                                                <option value="">เลือกประเภทของงาน</option>
                                                @foreach(\App\Models\PurchaseRequisition::getWorkTypeOptions() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('work_type') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('work_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- วิธีการจัดหาและวงเงิน -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">วิธีการจัดหา</label>
                                            <select class="form-select @error('procurement_method') is-invalid @enderror" name="procurement_method">
                                                <option value="">เลือกวิธีการจัดหา...</option>
                                                @foreach(\App\Models\PurchaseRequisition::getProcurementMethodOptions() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('procurement_method') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('procurement_method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">วงเงินที่จัดหา</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('procurement_budget') is-invalid @enderror" 
                                                       name="procurement_budget" id="procurementBudget" value="{{ old('procurement_budget') }}" 
                                                       min="0" max="10000" step="0.01" placeholder="0.00">
                                                <span class="input-group-text">บาท</span>
                                            </div>
                                            <small class="text-muted">วงเงินต้องไม่เกิน 10,000 บาท</small>
                                            @error('procurement_budget')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div id="procurementBudgetError" class="invalid-feedback d-none">วงเงินต้องไม่เกิน 10,000 บาท</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ผู้รับผิดชอบต่างๆ -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">คณะกรรมการจัดหาพัสดุ</label>
                                            <select class="form-select @error('procurement_committee_id') is-invalid @enderror" name="procurement_committee_id">
                                                <option value="">เลือกคณะกรรมการจัดหาพัสดุ...</option>
                                                @foreach($procurementCommitteeUsers as $user)
                                                    <option value="{{ $user->id }}" {{ old('procurement_committee_id') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('procurement_committee_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ผู้อนุมัติ PR</label>
                                            <select class="form-select @error('pr_approver_id') is-invalid @enderror" name="pr_approver_id">
                                                <option value="">เลือกผู้อนุมัติ PR...</option>
                                                @foreach($approverUsers as $user)
                                                    <option value="{{ $user->id }}" {{ old('pr_approver_id') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pr_approver_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ผู้เกี่ยวข้องอื่น</label>
                                            <select class="form-select @error('other_stakeholder_id') is-invalid @enderror" name="other_stakeholder_id">
                                                <option value="">เลือกผู้เกี่ยวข้องอื่น...</option>
                                                @foreach($otherStakeholderUsers as $user)
                                                    <option value="{{ $user->id }}" {{ old('other_stakeholder_id') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('other_stakeholder_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">หัวข้อ/ชื่อรายการ <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                                   name="title" value="{{ old('title') }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">แผนก <span class="text-danger">*</span></label>
                                            <select class="form-select @error('department_id') is-invalid @enderror" 
                                                    name="department_id" required>
                                                <option value="">-- เลือกแผนก --</option>
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

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">รายละเอียด</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                      name="description" rows="3">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลเพิ่มเติมสำหรับจัดซื้อตรง -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>ข้อมูลการจัดซื้อตรง</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ผู้จัดทำคำขอ <span class="text-danger">*</span></label>
                                            <select class="form-select @error('prepared_by_id') is-invalid @enderror" 
                                                    name="prepared_by_id" required>
                                                <option value="">-- เลือกผู้จัดทำ --</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ old('prepared_by_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }} ({{ $user->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('prepared_by_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">บริษัทที่จัดหา <span class="text-danger">*</span></label>
                                            <select class="form-select @error('supplier_vendor_id') is-invalid @enderror" 
                                                    name="supplier_vendor_id" required>
                                                <option value="">-- เลือกผู้ขาย --</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" {{ old('supplier_vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                        {{ $vendor->company_name }} ({{ $vendor->tax_id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('supplier_vendor_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">IO (Internal Order) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('io_number') is-invalid @enderror" 
                                                   name="io_number" value="{{ old('io_number') }}" placeholder="เช่น IO-2024-001" required>
                                            @error('io_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('cost_center') is-invalid @enderror" 
                                                   name="cost_center" value="{{ old('cost_center') }}" placeholder="เช่น CC-1001" required>
                                            @error('cost_center')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">เอกสารอ้างอิง</label>
                                            <input type="text" class="form-control @error('reference_document') is-invalid @enderror" 
                                                   name="reference_document" value="{{ old('reference_document') }}" placeholder="เช่น INV-2024-001">
                                            @error('reference_document')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">วันที่ต้องการ <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('required_date') is-invalid @enderror" 
                                                   name="required_date" value="{{ old('required_date', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                            @error('required_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ความเร่งด่วน <span class="text-danger">*</span></label>
                                            <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
                                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>ปกติ</option>
                                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>ด่วน</option>
                                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>ด่วนมาก</option>
                                            </select>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">วัตถุประสงค์ <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('purpose') is-invalid @enderror" 
                                                   name="purpose" value="{{ old('purpose') }}" required>
                                            @error('purpose')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- รายการสินค้า -->
                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>รายการสินค้า/บริการ</h6>
                                <button type="button" class="btn btn-sm btn-success" id="addItem">
                                    <i class="fas fa-plus me-1"></i> เพิ่มรายการ
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%">รายละเอียด</th>
                                                <th style="width: 10%">จำนวน</th>
                                                <th style="width: 10%">หน่วย</th>
                                                <th style="width: 15%">ราคาต่อหน่วย</th>
                                                <th style="width: 15%">รวม</th>
                                                <th style="width: 10%">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            <tr class="item-row">
                                                <td>
                                                    <input type="text" class="form-control item-description" name="items[0][description]" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-quantity" name="items[0][quantity]" 
                                                           value="1" min="1" step="1" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control item-unit" name="items[0][unit]" value="ชิ้น" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-price" name="items[0][unit_price]" 
                                                           value="0" min="0" step="0.01" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control item-total" name="items[0][total]" 
                                                           value="0" min="0" step="0.01" readonly style="background-color: #f8f9fa;">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger remove-item">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">ยอดรวมทั้งสิ้น:</th>
                                                <th id="grandTotal" class="text-danger">0.00</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <th colspan="6">
                                                    <div class="alert alert-warning mb-0">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>คำเตือน:</strong> ยอดรวมต้องไม่เกิน 10,000 บาท
                                                        <span class="float-end">
                                                            ยอดคงเหลือ: <span id="remainingAmount" class="badge bg-success">10,000.00</span> บาท
                                                        </span>
                                                    </div>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มดำเนินการ -->
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                            <i class="fas fa-save me-1"></i> บันทึกใบขอซื้อตรง
                                        </button>
                                        <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i> ยกเลิก
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    let itemIndex = 1;
    const maxAmount = 10000;

    // Update clause display
    $('#clauseNumber').change(function() {
        const clauseNum = $(this).val();
        if (clauseNum) {
            $('#clauseText').text('คุณเลือกข้อ ' + clauseNum);
        } else {
            $('#clauseText').text('กรุณาเลือกข้อ');
        }
    });

    // Validate procurement budget
    $('#procurementBudget').on('input', function() {
        const budget = parseFloat($(this).val()) || 0;
        const maxBudget = 10000;
        
        if (budget > maxBudget) {
            $(this).addClass('is-invalid');
            $('#procurementBudgetError').removeClass('d-none');
            $(this).val(maxBudget); // Auto-correct to max value
        } else {
            $(this).removeClass('is-invalid');
            $('#procurementBudgetError').addClass('d-none');
        }
    });

    // Calculate row total (similar to regular PR form)
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
        const price = parseFloat(row.find('.item-price').val()) || 0;
        const total = quantity * price;
        
        row.find('.item-total').val(total.toFixed(2));
    }

    // Calculate grand total
    function updateGrandTotal() {
        let grandTotal = 0;
        $('.item-total').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        
        $('#grandTotal').text(grandTotal.toFixed(2));
        
        const remaining = maxAmount - grandTotal;
        $('#remainingAmount').text(remaining.toFixed(2));
        
        if (grandTotal > maxAmount) {
            $('#grandTotal').removeClass('text-success').addClass('text-danger');
            $('#remainingAmount').removeClass('bg-success').addClass('bg-danger');
            $('#submitBtn').prop('disabled', true);
            
            if (!$('#amountAlert').length) {
                const alert = `
                    <div id="amountAlert" class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>เกินวงเงิน!</strong> ยอดรวม ${grandTotal.toFixed(2)} บาท เกินกว่าที่กำหนด (10,000 บาท)
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('.card-body').first().prepend(alert);
            }
        } else {
            $('#grandTotal').removeClass('text-danger').addClass('text-success');
            $('#remainingAmount').removeClass('bg-danger').addClass('bg-success');
            $('#submitBtn').prop('disabled', false);
            $('#amountAlert').remove();
        }
    }

    // Calculate item total when quantity or price changes (use same event as regular PR)
    $(document).on('input', '.item-quantity, .item-price', function() {
        const row = $(this).closest('tr');
        calculateRowTotal(row);
        updateGrandTotal();
        
        // Visual feedback
        const totalField = row.find('.item-total');
        totalField.addClass('bg-success').addClass('text-white');
        setTimeout(() => {
            totalField.removeClass('bg-success').removeClass('text-white');
        }, 300);
    });

    // Add new item row
    $('#addItem').click(function() {
        const newRow = `
            <tr class="item-row">
                <td>
                    <input type="text" class="form-control item-description" name="items[${itemIndex}][description]" required>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemIndex}][quantity]" 
                           value="1" min="1" step="1" required>
                </td>
                <td>
                    <input type="text" class="form-control item-unit" name="items[${itemIndex}][unit]" value="ชิ้น" required>
                </td>
                <td>
                    <input type="number" class="form-control item-price" name="items[${itemIndex}][unit_price]" 
                           value="0" min="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" class="form-control item-total" name="items[${itemIndex}][total]" 
                           value="0" min="0" step="0.01" readonly style="background-color: #f8f9fa;">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsBody').append(newRow);
        itemIndex++;
        updateGrandTotal();
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            updateGrandTotal();
        } else {
            alert('ต้องมีรายการอย่างน้อย 1 รายการ');
        }
    });

    // Calculate totals on page load
    $('.item-row').each(function() {
        calculateRowTotal($(this));
    });
    updateGrandTotal();

    // Form validation before submit
    $('#directPurchaseForm').submit(function(e) {
        const grandTotal = parseFloat($('#grandTotal').text());
        if (grandTotal > maxAmount) {
            e.preventDefault();
            alert('ยอดรวมเกิน 10,000 บาท กรุณาตรวจสอบรายการอีกครั้ง');
            return false;
        }
        
        if (grandTotal === 0) {
            e.preventDefault();
            alert('กรุณาเพิ่มรายการสินค้า');
            return false;
        }
    });
});
</script>
@endpush
@endsection