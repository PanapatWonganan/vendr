@extends('layouts.app')

@section('title', 'ข้อมูลพนักงาน')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">ข้อมูลพนักงาน</h4>
        <p class="text-muted">รายละเอียดข้อมูลของ: {{ $user->name }}</p>
    </div>
    <div>
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning text-white me-2">
            <i class="fas fa-edit me-1"></i>แก้ไข
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>กลับ
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="content-card mb-4">
            <div class="card-header">
                <h5>ข้อมูลพื้นฐาน</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="table-header" style="width: 30%">รหัสพนักงาน</th>
                                <td>{{ $user->id }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">ชื่อ</th>
                                <td>{{ $user->name }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">อีเมล</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">แผนก</th>
                                <td>{{ $user->department ? $user->department->name : 'ไม่ระบุ' }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">วันที่สร้าง</th>
                                <td>{{ $user->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">วันที่อัปเดต</th>
                                <td>{{ $user->updated_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th class="table-header">ยืนยันอีเมล</th>
                                <td>
                                    @if($user->email_verified_at)
                                        <span class="badge bg-success">
                                            ยืนยันแล้ว ({{ $user->email_verified_at->format('d/m/Y H:i:s') }})
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            ยังไม่ยืนยัน
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="content-card">
            <div class="card-header">
                <h5>บทบาท</h5>
            </div>
            <div class="card-body">
                @if($user->roles->count() > 0)
                    @foreach($user->roles as $role)
                        <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-primary">
                                    {{ $role->display_name }}
                                </span>
                            </div>
                            <p class="mb-1">{{ $role->description }}</p>
                            <p class="text-muted small">
                                กำหนดเมื่อ: {{ $role->pivot->assigned_at ? \Carbon\Carbon::parse($role->pivot->assigned_at)->format('d/m/Y H:i:s') : 'ไม่ระบุ' }}
                            </p>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">ไม่มีบทบาทที่กำหนด</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 