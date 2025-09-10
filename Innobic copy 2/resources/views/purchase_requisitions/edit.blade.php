@extends('layouts.app')

@section('title', 'แก้ไขใบขอซื้อ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit me-2"></i>แก้ไขใบขอซื้อ #{{ $purchaseRequisition->pr_number }}
                    </h6>
                    <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> กลับไปยังรายการ
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchase-requisitions.update', $purchaseRequisition) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- ข้อมูลทั่วไป -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pr_number" class="form-label">เลขที่ใบขอซื้อ</label>
                                    <input type="text" class="form-control" id="pr_number" value="{{ $purchaseRequisition->pr_number }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="request_date" class="form-label">วันที่ขอซื้อ</label>
                                    <input type="date" class="form-control" id="request_date" value="{{ $purchaseRequisition->created_at->format('Y-m-d') }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="required_date" class="form-label">วันที่ต้องการ <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('required_date') is-invalid @enderror" 
                                           id="required_date" name="required_date" value="{{ old('required_date', $purchaseRequisition->required_date->format('Y-m-d')) }}" required>
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
                                        <option value="">-- เลือกแผนก --</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id', $purchaseRequisition->department_id) == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="requester_name" class="form-label">ชื่อผู้ขอ</label>
                                    <input type="text" class="form-control" id="requester_name" 
                                           value="{{ $purchaseRequisition->requester->name ?? 'ไม่ระบุผู้ขอ' }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">สถานะปัจจุบัน</label>
                                    <div>
                                        @php
                                            $statusClass = [
                                                'draft' => 'bg-secondary',
                                                'pending' => 'bg-warning text-dark',
                                                'pending_approval' => 'bg-warning text-dark',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'processing' => 'bg-info text-dark',
                                                'completed' => 'bg-primary',
                                                'cancelled' => 'bg-dark'
                                            ];
                                            $statusText = [
                                                'draft' => 'แบบร่าง',
                                                'pending' => 'รออนุมัติ',
                                                'pending_approval' => 'รออนุมัติ',
                                                'approved' => 'อนุมัติแล้ว',
                                                'rejected' => 'ไม่อนุมัติ',
                                                'processing' => 'กำลังดำเนินการ',
                                                'completed' => 'เสร็จสมบูรณ์',
                                                'cancelled' => 'ยกเลิก'
                                            ];
                                        @endphp
                                        <span class="badge {{ $statusClass[$purchaseRequisition->status] }}">
                                            {{ $statusText[$purchaseRequisition->status] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ชื่อใบขอซื้อ -->
                        <div class="mb-3">
                            <label for="title" class="form-label">ชื่อใบขอซื้อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $purchaseRequisition->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- หมวดหมู่ -->
                        <div class="mb-3">
                            <label for="category" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" 
                                    id="category" name="category" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                @foreach(\App\Models\PurchaseRequisition::getCategoryOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('category', $purchaseRequisition->category) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">เลือกประเภทหมวดหมู่ของใบขอซื้อ</small>
                        </div>

                        <div class="col-md-6">
                            <label for="work_type" class="form-label">ประเภทของงาน <span class="text-danger">*</span></label>
                            <select id="work_type" name="work_type" class="form-select" required>
                                <option value="">เลือกประเภทของงาน</option>
                                @foreach(\App\Models\PurchaseRequisition::getWorkTypeOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('work_type', $purchaseRequisition->work_type) == $value ? 'selected' : '' }}>
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
                                    <option value="{{ $value }}" {{ old('procurement_method', $purchaseRequisition->procurement_method) == $value ? 'selected' : '' }}>
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
                                       value="{{ old('procurement_budget', $purchaseRequisition->procurement_budget) }}" min="0" step="0.01" placeholder="0.00">
                                <span class="input-group-text">บาท</span>
                            </div>
                            @error('procurement_budget')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="delivery_schedule" class="form-label">งวดการส่งมอบ</label>
                            <textarea class="form-control" id="delivery_schedule" name="delivery_schedule" 
                                      placeholder="ระบุงวดการส่งมอบ...">{{ old('delivery_schedule', $purchaseRequisition->delivery_schedule) }}</textarea>
                            @error('delivery_schedule')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="payment_schedule" class="form-label">งวดการจ่ายเงิน</label>
                            <textarea class="form-control" id="payment_schedule" name="payment_schedule" 
                                      placeholder="ระบุงวดการจ่ายเงิน...">{{ old('payment_schedule', $purchaseRequisition->payment_schedule) }}</textarea>
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
                                    <option value="{{ $user->id }}" {{ old('procurement_committee_id', $purchaseRequisition->procurement_committee_id) == $user->id ? 'selected' : '' }}>
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
                                    <option value="{{ $user->id }}" {{ old('inspection_committee_id', $purchaseRequisition->inspection_committee_id) == $user->id ? 'selected' : '' }}>
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
                                    <option value="{{ $user->id }}" {{ old('pr_approver_id', $purchaseRequisition->pr_approver_id) == $user->id ? 'selected' : '' }}>
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
                                    <option value="{{ $user->id }}" {{ old('other_stakeholder_id', $purchaseRequisition->other_stakeholder_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('other_stakeholder_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                      
                    <!-- รายการสินค้า -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">รายการสินค้า <span class="text-danger">*</span></label>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">ลำดับ</th>
                                            <th width="30%">รายการสินค้า/บริการ</th>
                                            <th width="10%">จำนวน</th>
                                            <th width="10%">หน่วย</th>
                                            <th width="15%">ราคาต่อหน่วย</th>
                                            <th width="15%">ราคารวม</th>
                                            <th width="15%">การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchaseRequisition->items as $index => $item)
                                        <tr class="item-row">
                                            <td class="item-number">{{ $index + 1 }}</td>
                                            <td>
                                                <input type="text" class="form-control item-description" 
                                                       name="items[{{ $index }}][description]" value="{{ $item->description }}" required>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            </td>
                                            <td>
                                                <input type="number" min="1" step="1" class="form-control item-quantity" 
                                                       name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-unit" 
                                                       name="items[{{ $index }}][unit]" value="{{ $item->unit }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-price" 
                                                       name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-total" 
                                                       name="items[{{ $index }}][total_price]" value="{{ $item->total_price }}">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm delete-row">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7">
                                                <button type="button" id="add-row" class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> เพิ่มรายการ
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>รวมทั้งสิ้น:</strong></td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control" id="grand-total" name="total_amount" value="{{ $purchaseRequisition->total_amount }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- เหตุผลที่ขอซื้อ -->
                        <div class="mb-3">
                            <label for="description" class="form-label">เหตุผลที่ขอซื้อ</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $purchaseRequisition->description) }}</textarea>
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
                                           id="currency" name="currency" value="{{ old('currency', $purchaseRequisition->currency) }}" required>
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
                                        <option value="low" {{ old('priority', $purchaseRequisition->priority) == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                        <option value="medium" {{ old('priority', $purchaseRequisition->priority) == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                        <option value="high" {{ old('priority', $purchaseRequisition->priority) == 'high' ? 'selected' : '' }}>สูง</option>
                                        <option value="urgent" {{ old('priority', $purchaseRequisition->priority) == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
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
                                        @foreach($purchaseRequisition->items as $index => $item)
                                        <tr class="item-row">
                                            <td class="item-number">{{ $index + 1 }}</td>
                                            <td>
                                                <input type="text" class="form-control item-description" 
                                                       name="items[{{ $index }}][description]" value="{{ $item->description }}" required>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            </td>
                                            <td>
                                                <input type="number" min="1" step="1" class="form-control item-quantity" 
                                                       name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-unit" 
                                                       name="items[{{ $index }}][unit]" value="{{ $item->unit }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-price" 
                                                       name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control item-total" 
                                                       name="items[{{ $index }}][total_price]" value="{{ $item->total_price }}">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm delete-row">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7">
                                                <button type="button" id="add-row" class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> เพิ่มรายการ
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>รวมทั้งสิ้น:</strong></td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control" id="grand-total" name="total_amount" value="{{ $purchaseRequisition->total_amount }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- หมายเหตุ -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $purchaseRequisition->notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                                <i class="fas fa-times me-1"></i>ยกเลิก
                            </button>
                            @if($purchaseRequisition->status === 'draft' || $purchaseRequisition->status === 'rejected')
                            <button type="submit" name="save_as_draft" value="1" class="btn btn-info me-2">
                                <i class="fas fa-save me-1"></i>บันทึกเป็นแบบร่าง
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>ส่งเพื่อขออนุมัติ
                            </button>
                            @else
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // คำนวณราคารวมเมื่อมีการเปลี่ยนแปลงจำนวนหรือราคาต่อหน่วย
        $(document).on('input', '.item-quantity, .item-price', function() {
            calculateRowTotal($(this).closest('tr'));
            updateGrandTotal();
        });
        
        // เพิ่มแถวใหม่
        $('#add-row').click(function() {
            let newRow = createNewRow();
            $('#items-table tbody').append(newRow);
            renumberRows();
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
                           name="items[${newRowIndex}][quantity]" value="1" required>
                </td>
                <td>
                    <input type="text" class="form-control item-unit" 
                           name="items[${newRowIndex}][unit]" value="ชิ้น" required>
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control item-price" 
                           name="items[${newRowIndex}][unit_price]" value="0.00" required>
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control item-total" 
                           name="items[${newRowIndex}][total_price]" value="0.00">
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
        
        // ถ้าไม่ได้กำลังแก้ไขอยู่ จึงค่อยกำหนดค่า
        if (!row.find('.item-total').is(':focus')) {
            row.find('.item-total').val(total.toFixed(2));
        }
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
            
            // ถ้ามี hidden input สำหรับ id ให้อัพเดทด้วย
            if ($(this).find('input[name^="items"][name$="[id]"]').length) {
                $(this).find('input[name^="items"][name$="[id]"]').attr('name', `items[${index}][id]`);
            }
        });
    }
    
    // คำนวณราคารวมทั้งหมด
    function updateGrandTotal() {
        let grandTotal = 0;
        $('.item-total').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        
        $('#grand-total').val(grandTotal.toFixed(2));
    }
</script>
@endsection 