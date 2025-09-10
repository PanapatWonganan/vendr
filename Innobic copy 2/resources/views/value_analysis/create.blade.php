@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('สร้าง Value Analysis') }}
    </h2>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">สร้าง Value Analysis ใหม่</h5>
                    <a href="{{ route('value-analysis.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{ route('value-analysis.store') }}" method="POST" id="vaForm">
                        @csrf

                        <div class="row">
                            <!-- เลขที่ PR -->
                            <div class="col-md-6 mb-3">
                                <label for="purchase_requisition_id" class="form-label">เลขที่ PR <span class="text-danger">*</span></label>
                                <select class="form-select @error('purchase_requisition_id') is-invalid @enderror" 
                                        id="purchase_requisition_id" 
                                        name="purchase_requisition_id" 
                                        required onchange="loadPRDetails()">
                                    <option value="">-- เลือกใบ PR --</option>
                                    @foreach($purchaseRequisitions as $pr)
                                        <option value="{{ $pr->id }}" {{ old('purchase_requisition_id') == $pr->id ? 'selected' : '' }}>
                                            {{ $pr->pr_number }} - {{ $pr->title }} 
                                            ({{ $pr->department->name ?? 'ไม่ระบุแผนก' }}) 
                                            [{{ number_format($pr->total_amount, 2) }} {{ $pr->currency }}]
                                        </option>
                                    @endforeach
                                </select>
                                @error('purchase_requisition_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">เลือกใบ PR ที่ได้รับอนุมัติแล้วและยังไม่มี Value Analysis</small>
                            </div>

                            <!-- ข้อมูลสรุป PR -->
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">ข้อมูลสรุป PR</h6>
                                        <div id="pr-summary" class="d-none">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">แผนก:</small><br>
                                                    <span id="pr-department">-</span>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">ผู้ขอ:</small><br>
                                                    <span id="pr-requester">-</span>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <small class="text-muted">หัวข้อ:</small><br>
                                                    <span id="pr-title">-</span>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <small class="text-muted">งบประมาณ:</small><br>
                                                    <span id="pr-amount" class="fw-bold text-primary">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="pr-empty" class="text-muted text-center">
                                            <i class="fas fa-info-circle"></i> กรุณาเลือก PR ก่อน
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ชื่อใบขอซื้อ -->
                        <div class="mb-3">
                            <label for="pr_title_display" class="form-label">ชื่อใบขอซื้อ</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pr_title_display" 
                                   placeholder="-- เลือก PR เพื่อแสดงชื่อใบขอซื้อ --" 
                                   readonly 
                                   style="background-color: #f8f9fa;">
                            <small class="form-text text-muted">ข้อมูลจะแสดงอัตโนมัติตาม PR ที่เลือก</small>
                        </div>

                        <div class="row">
                            <!-- ประเภทงาน -->
                            <div class="col-md-6 mb-3">
                                <label for="work_type_display" class="form-label">ประเภทงาน</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="work_type_display" 
                                       placeholder="-- เลือก PR เพื่อแสดงประเภทงาน --" 
                                       readonly 
                                       style="background-color: #f8f9fa;">
                                <small class="form-text text-muted">ข้อมูลจะแสดงอัตโนมัติตาม PR ที่เลือก</small>
                            </div>

                            <!-- วิธีจัดหา -->
                            <div class="col-md-6 mb-3">
                                <label for="procurement_method_display" class="form-label">วิธีจัดหา</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="procurement_method_display" 
                                       placeholder="-- เลือก PR เพื่อแสดงวิธีจัดหา --" 
                                       readonly 
                                       style="background-color: #f8f9fa;">
                                <small class="form-text text-muted">ข้อมูลจะแสดงอัตโนมัติตาม PR ที่เลือก</small>
                            </div>
                        </div>

                        <!-- New fields -->
                        <div class="row mb-3">
                            <!-- จัดหาจาก -->
                            <div class="col-md-6">
                                <label for="procured_from" class="form-label">จัดหาจาก</label>
                                <textarea class="form-control" id="procured_from" name="procured_from" rows="3" 
                                        placeholder="ระบุแหล่งที่มาในการจัดหา..."></textarea>
                                @error('procured_from')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- วงเงินที่ตกลงซื้อหรือจ้าง -->
                            <div class="col-md-6">
                                <label for="agreed_amount" class="form-label">วงเงินที่ตกลงซื้อหรือจ้าง (สกุลเงินบาท)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="agreed_amount" name="agreed_amount" 
                                           step="0.01" min="0" placeholder="0.00">
                                    <span class="input-group-text">บาท</span>
                                </div>
                                @error('agreed_amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Preview การต่อรองราคา -->
                        <div class="card mb-4" id="price-negotiation-preview" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-handshake text-info me-2"></i>
                                    ตัวอย่างข้อมูลการต่อรองราคา
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">วงเงินในการจัดหา:</label>
                                        <div class="h6" id="preview-total-budget">-</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">วงเงินที่ตกลงซื้อจ้าง:</label>
                                        <div class="h6" id="preview-agreed-amount">-</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">ส่วนต่าง:</label>
                                        <div class="h6" id="preview-difference">-</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">ผลการต่อรองราคา:</label>
                                        <div class="h6" id="preview-result">-</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" id="preview-progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มการดำเนินการ -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('value-analysis.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> ยกเลิก
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> สร้าง Value Analysis
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadPRDetails() {
    const prId = document.getElementById('purchase_requisition_id').value;
    
    if (!prId) {
        // Clear displays when no PR selected
        document.getElementById('pr_title_display').value = '';
                  document.getElementById('work_type_display').value = '';
          document.getElementById('procurement_method_display').value = '';
          // Don't clear manual input fields - user may want to keep their data
          document.getElementById('pr-summary').classList.add('d-none');
          document.getElementById('pr-empty').classList.remove('d-none');
        return;
    }

    // Show loading
    document.getElementById('pr_title_display').value = 'กำลังโหลด...';
          document.getElementById('work_type_display').value = 'กำลังโหลด...';
      document.getElementById('procurement_method_display').value = 'กำลังโหลด...';
      // Don't show loading for manual input fields

    // Fetch PR details via AJAX
    fetch(`{{ url('value-analysis/pr-details') }}/${prId}`)
        .then(response => response.json())
        .then(data => {
            // Update PR title, work type and procurement method
            document.getElementById('pr_title_display').value = data.title || 'ไม่ระบุ';
            document.getElementById('work_type_display').value = data.work_type_label || 'ไม่ระบุ';
            document.getElementById('procurement_method_display').value = data.procurement_method_label || 'ไม่ระบุ';
            
            // Update PR summary
            document.getElementById('pr-department').textContent = data.department || '-';
            document.getElementById('pr-requester').textContent = data.requester || '-';
            document.getElementById('pr-title').textContent = data.title || '-';
            document.getElementById('pr-amount').textContent = `${data.total_amount} ${data.currency}`;
            
            // Show summary and hide empty message
                                        document.getElementById('pr-summary').classList.remove('d-none');
              document.getElementById('pr-empty').classList.add('d-none');
              
              // Manual input fields don't need auto-population from PR data
              
              // Update preview section after data is loaded
              setTimeout(function() {
                  updatePriceNegotiationPreview();
              }, 100);
          })
          .catch(error => {
            console.error('Error:', error);
            document.getElementById('pr_title_display').value = 'เกิดข้อผิดพลาด';
                          document.getElementById('work_type_display').value = 'เกิดข้อผิดพลาด';
              document.getElementById('procurement_method_display').value = 'เกิดข้อผิดพลาด';
              // Don't modify manual input fields on error
              alert('เกิดข้อผิดพลาดในการโหลดข้อมูล PR');
        });
}

// Load PR details if there's a selected value on page load (for old input)
document.addEventListener('DOMContentLoaded', function() {
    const selectedPR = document.getElementById('purchase_requisition_id').value;
    if (selectedPR) {
        loadPRDetails();
    }
      });

      // Update price negotiation preview
      function updatePriceNegotiationPreview() {
          const prAmountElement = document.getElementById('pr-amount');
          const prAmountText = prAmountElement ? prAmountElement.textContent : '';
          
          const totalBudget = prAmountElement ? parseFloat(prAmountText.replace(/[^\d.-]/g, '')) || 0 : 0;
          const agreedAmount = parseFloat(document.getElementById('agreed_amount').value) || 0;
          
          // Only show preview if we have PR data
          if (totalBudget > 0) {
              document.getElementById('price-negotiation-preview').style.display = 'block';
              
              // Update values
              document.getElementById('preview-total-budget').innerHTML = 
                  '<span class="text-primary">' + totalBudget.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท</span>';
              
              if (agreedAmount > 0) {
                  document.getElementById('preview-agreed-amount').innerHTML = 
                      '<span class="text-warning">' + agreedAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท</span>';
                  
                  const difference = totalBudget - agreedAmount;
                  const percentage = (difference / totalBudget) * 100;
                  
                  // Update difference
                  const diffClass = difference > 0 ? 'text-success' : (difference < 0 ? 'text-danger' : 'text-info');
                  document.getElementById('preview-difference').innerHTML = 
                      '<span class="' + diffClass + '">' + difference.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท</span>';
                  
                  // Update result
                  let resultText = '';
                  if (percentage > 0) {
                      resultText = '<span class="text-success fw-bold">ประหยัดได้ ' + percentage.toFixed(2) + '%</span>';
                  } else if (percentage < 0) {
                      resultText = '<span class="text-danger fw-bold">เกินงบประมาณ ' + Math.abs(percentage).toFixed(2) + '%</span>';
                  } else {
                      resultText = '<span class="text-info fw-bold">ตรงตามงบประมาณ</span>';
                  }
                  document.getElementById('preview-result').innerHTML = resultText;
                  
                  // Update progress bar
                  const barWidth = Math.min(Math.abs(percentage), 100);
                  const barClass = percentage > 0 ? 'bg-success' : (percentage < 0 ? 'bg-danger' : 'bg-info');
                  const progressBar = document.getElementById('preview-progress-bar');
                  progressBar.style.width = barWidth + '%';
                  progressBar.className = 'progress-bar ' + barClass;
                  progressBar.textContent = percentage > 0 ? 'ประหยัด ' + percentage.toFixed(2) + '%' : 
                                           (percentage < 0 ? 'เกิน ' + Math.abs(percentage).toFixed(2) + '%' : 'ตรงงบ 0%');
              } else {
                  document.getElementById('preview-agreed-amount').innerHTML = '<span class="text-muted">กรุณากรอกวงเงินที่ตกลง</span>';
                  document.getElementById('preview-difference').innerHTML = '<span class="text-muted">-</span>';
                  document.getElementById('preview-result').innerHTML = '<span class="text-muted">-</span>';
                  document.getElementById('preview-progress-bar').style.width = '0%';
                  document.getElementById('preview-progress-bar').textContent = '';
              }
          } else {
              document.getElementById('price-negotiation-preview').style.display = 'none';
          }
      }

      // Add event listener for agreed amount field when page loads
      document.addEventListener('DOMContentLoaded', function() {
          const agreedAmountField = document.getElementById('agreed_amount');
          if (agreedAmountField) {
              agreedAmountField.addEventListener('input', updatePriceNegotiationPreview);
          }
      });
  </script>
@endsection 