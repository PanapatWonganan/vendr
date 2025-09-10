@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        จัดการใบ PO (Purchase Orders)
                    </h3>
                    <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>สร้างใบ PO ใหม่
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">สถานะ</label>
                                <select name="status" class="form-select">
                                    <option value="">ทั้งหมด</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ร่าง</option>
                                    <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>รออนุมัติ</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                                    <option value="sent_to_supplier" {{ request('status') == 'sent_to_supplier' ? 'selected' : '' }}>ส่งให้ผู้ขายแล้ว</option>
                                    <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>ผู้ขายรับทราบแล้ว</option>
                                    <option value="partially_received" {{ request('status') == 'partially_received' ? 'selected' : '' }}>รับบางส่วน</option>
                                    <option value="fully_received" {{ request('status') == 'fully_received' ? 'selected' : '' }}>รับครบแล้ว</option>
                                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>ปิดงาน</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ระดับความสำคัญ</label>
                                <select name="priority" class="form-select">
                                    <option value="">ทั้งหมด</option>
                                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>ต่ำ</option>
                                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>สูง</option>
                                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">แผนก</label>
                                <select name="department" class="form-select">
                                    <option value="">ทั้งหมด</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ค้นหา</label>
                                <div class="input-group">
                                    <input type="text" name="query" class="form-control" 
                                           placeholder="เลข PO, ชื่อ, ผู้ขาย..." 
                                           value="{{ request('query') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-1"></i>กรอง
                                </button>
                                <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>เคลียร์ฟิลเตอร์
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Results -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>เลข PO</th>
                                    <th>ชื่อ PO</th>
                                    <th>ผู้ขาย</th>
                                    <th>แผนก</th>
                                    <th>จำนวนเงิน</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ระดับความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $po)
                                    <tr>
                                        <td>
                                            <a href="{{ route('purchase-orders.show', $po) }}" class="fw-bold text-decoration-none">
                                                {{ $po->po_number }}
                                            </a>
                                        </td>
                                        <td>{{ $po->po_title ?? 'N/A' }}</td>
                                        <td>{{ $po->vendor_name ?? $po->supplier?->name ?? 'N/A' }}</td>
                                        <td>{{ $po->department->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($po->total_amount, 2) }} {{ $po->currency }}</td>
                                        <td>{{ $po->order_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $po->priority_badge }} rounded-pill">
                                                {{ $po->priority_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $po->status_badge }} rounded-pill">
                                                {{ $po->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('purchase-orders.show', $po) }}" 
                                                   class="btn btn-outline-primary" title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($po->canEdit())
                                                    <a href="{{ route('purchase-orders.edit', $po) }}" 
                                                       class="btn btn-outline-secondary" title="แก้ไข">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmDelete({{ $po->id }})" title="ลบ">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">ไม่พบข้อมูลใบ PO</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($purchaseOrders->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $purchaseOrders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                คุณแน่ใจหรือไม่ที่จะลบใบ PO นี้? การกระทำนี้ไม่สามารถยกเลิกได้
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(poId) {
    const form = document.getElementById('deleteForm');
    form.action = `/purchase-orders/${poId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush
@endsection 