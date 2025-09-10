@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลพนักงาน')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">แก้ไขข้อมูลพนักงาน</h4>
        <p class="text-muted">แก้ไขข้อมูลสำหรับ: {{ $user->name }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการ
    </a>
</div>

<div class="content-card">
    <div class="card-header">
        <h5>ข้อมูลพนักงาน</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">รหัสผ่าน <span class="text-muted">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</span></label>
                    <div class="input-group">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="password-confirm" class="form-label">ยืนยันรหัสผ่าน</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>

            <div class="mb-3">
                <label for="department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                <select id="department_id" class="form-select @error('department_id') is-invalid @enderror" name="department_id" required>
                    <option value="">เลือกแผนก</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (old('department_id', $user->department_id) == $department->id) ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label d-block">บทบาท <span class="text-danger">*</span></label>
                <div class="bg-surface p-3 rounded border-light">
                    @foreach($roles as $role)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}" 
                                {{ (is_array(old('roles')) && in_array($role->id, old('roles'))) || 
                                   (!is_array(old('roles')) && in_array($role->id, $userRoleIds)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role-{{ $role->id }}">
                                <strong>{{ $role->display_name }}</strong>
                                <p class="text-muted small mb-0">{{ $role->description }}</p>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('roles')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-times me-1"></i> ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // Toggle the eye icon
                togglePassword.querySelector('i').classList.toggle('fa-eye');
                togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    });
</script>
@endpush 