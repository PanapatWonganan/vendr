@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Value Analysis') }}
    </h2>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">รายการ Value Analysis</h5>
                    <a href="{{ route('value-analysis.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> สร้าง Value Analysis ใหม่
                    </a>
                </div>

                <div class="card-body">
                    @if($valueAnalyses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>เลขที่ VA</th>
                                        <th>เลขที่ PR</th>
                                        <th>หัวข้อ PR</th>
                                        <th>ประเภทงาน</th>
                                        <th>วิธีจัดหา</th>
                                        <th>งบประมาณ</th>
                                        <th>สถานะ</th>
                                        <th>ผู้สร้าง</th>
                                        <th>วันที่สร้าง</th>
                                        <th class="text-center">การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($valueAnalyses as $va)
                                        <tr>
                                            <td>
                                                <strong>{{ $va->va_number }}</strong>
                                            </td>
                                            <td>
                                                @if($va->purchaseRequisition)
                                                    <a href="{{ route('purchase-requisitions.show', $va->purchaseRequisition->id) }}" 
                                                       class="text-decoration-none">
                                                        {{ $va->purchaseRequisition->pr_number }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $va->purchaseRequisition->title ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $va->work_type_label }}</span>
                                            </td>
                                            <td>
                                                @if($va->procurement_method)
                                                    <span class="badge bg-secondary">{{ $va->procurement_method_label }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ number_format($va->total_budget, 2) }}</strong> {{ $va->currency }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $va->status_badge }}">{{ $va->status_text }}</span>
                                            </td>
                                            <td>{{ $va->creator->name ?? 'ไม่ระบุ' }}</td>
                                            <td>{{ $va->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('value-analysis.show', $va) }}" 
                                                       class="btn btn-outline-primary" title="ดูรายละเอียด">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    @if($va->canEdit())
                                                        <a href="{{ route('value-analysis.edit', $va) }}" 
                                                           class="btn btn-outline-warning" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif

                                                    @if($va->status === 'draft')
                                                        <form action="{{ route('value-analysis.start-analysis', $va) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-success" 
                                                                    title="เริ่มการวิเคราะห์">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if($va->status === 'in_progress')
                                                        <form action="{{ route('value-analysis.complete-analysis', $va) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-info" 
                                                                    title="ทำเครื่องหมายเสร็จสิ้น">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if($va->canApprove())
                                                        <form action="{{ route('value-analysis.approve', $va) }}" 
                                                              method="POST" class="d-inline"
                                                              onsubmit="return confirm('ยืนยันการอนุมัติ Value Analysis นี้?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-success" 
                                                                    title="อนุมัติ">
                                                                <i class="fas fa-thumbs-up"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form action="{{ route('value-analysis.reject', $va) }}" 
                                                              method="POST" class="d-inline"
                                                              onsubmit="return confirm('ยืนยันการปฏิเสธ Value Analysis นี้?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger" 
                                                                    title="ปฏิเสธ">
                                                                <i class="fas fa-thumbs-down"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if($va->canEdit())
                                                        <form action="{{ route('value-analysis.destroy', $va) }}" 
                                                              method="POST" class="d-inline"
                                                              onsubmit="return confirm('ยืนยันการลบ Value Analysis นี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger" 
                                                                    title="ลบ">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $valueAnalyses->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ยังไม่มี Value Analysis</h5>
                            <p class="text-muted">เริ่มต้นสร้าง Value Analysis แรกของคุณ</p>
                            <a href="{{ route('value-analysis.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> สร้าง Value Analysis ใหม่
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 