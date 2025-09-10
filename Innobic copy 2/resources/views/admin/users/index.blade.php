@extends('layouts.app')

@section('title', 'จัดการพนักงาน')

@section('content')
<h4 class="page-title">จัดการพนักงาน</h4>
<p class="text-muted mb-4">เพิ่ม แก้ไข และจัดการข้อมูลพนักงานในระบบ</p>

<div class="content-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>รายชื่อพนักงาน</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i> เพิ่มพนักงานใหม่
        </button>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อ</th>
                        <th>อีเมล</th>
                        <th>แผนก</th>
                        <th>บทบาท</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department ? $user->department->name : 'ไม่ระบุ' }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <span class="badge bg-primary rounded-pill">{{ $role->display_name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white view-user-btn" 
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-email="{{ $user->email }}"
                                    data-user-department="{{ $user->department ? $user->department->name : 'ไม่ระบุ' }}"
                                    data-user-created="{{ $user->created_at->format('d/m/Y H:i:s') }}"
                                    data-user-updated="{{ $user->updated_at->format('d/m/Y H:i:s') }}"
                                    data-user-verified="{{ $user->email_verified_at ? 'ยืนยันแล้ว (' . $user->email_verified_at->format('d/m/Y H:i:s') . ')' : 'ยังไม่ยืนยัน' }}"
                                    data-user-roles="{{ json_encode($user->roles) }}"
                                    data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-warning text-white edit-user-btn" 
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-email="{{ $user->email }}"
                                    data-user-department="{{ $user->department_id }}"
                                    data-user-roles="{{ json_encode($user->roles->pluck('id')) }}"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจว่าต้องการลบผู้ใช้นี้?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">ไม่พบข้อมูลผู้ใช้</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel">เพิ่มพนักงานใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password-confirm" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select id="department_id" class="form-select @error('department_id') is-invalid @enderror" name="department_id" required>
                            <option value="">เลือกแผนก</option>
                            @foreach(\App\Models\Department::where('is_active', true)->get() as $department)
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
                        <label class="form-label d-block">บทบาท <span class="text-danger">*</span></label>
                        <div class="bg-surface p-3 rounded border-light">
                            @foreach(\App\Models\Role::where('is_active', true)->get() as $role)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}" {{ (is_array(old('roles')) && in_array($role->id, old('roles'))) ? 'checked' : '' }}>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> บันทึกข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewUserModalLabel">ข้อมูลพนักงาน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="table-header" style="width: 30%">รหัสพนักงาน</th>
                                <td id="view-user-id"></td>
                            </tr>
                            <tr>
                                <th class="table-header">ชื่อ</th>
                                <td id="view-user-name"></td>
                            </tr>
                            <tr>
                                <th class="table-header">อีเมล</th>
                                <td id="view-user-email"></td>
                            </tr>
                            <tr>
                                <th class="table-header">แผนก</th>
                                <td id="view-user-department"></td>
                            </tr>
                            <tr>
                                <th class="table-header">วันที่สร้าง</th>
                                <td id="view-user-created"></td>
                            </tr>
                            <tr>
                                <th class="table-header">วันที่อัปเดต</th>
                                <td id="view-user-updated"></td>
                            </tr>
                            <tr>
                                <th class="table-header">ยืนยันอีเมล</th>
                                <td id="view-user-verified"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h6 class="fw-bold">บทบาท</h6>
                    <div class="bg-surface p-3 rounded border-light" id="view-user-roles">
                        <!-- บทบาทจะถูกเพิ่มโดย JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-warning text-white" id="viewToEditButton">
                    <i class="fas fa-edit me-1"></i> แก้ไขข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลพนักงาน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input id="edit_name" type="text" class="form-control" name="name" required autocomplete="name">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input id="edit_email" type="email" class="form-control" name="email" required autocomplete="email">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label">รหัสผ่าน <span class="text-muted">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</span></label>
                            <div class="input-group">
                                <input id="edit_password" type="password" class="form-control" name="password" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_password_confirmation" class="form-label">ยืนยันรหัสผ่าน</label>
                            <input id="edit_password_confirmation" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_department_id" class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select id="edit_department_id" class="form-select" name="department_id" required>
                            <option value="">เลือกแผนก</option>
                            @foreach(\App\Models\Department::where('is_active', true)->get() as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">บทบาท <span class="text-danger">*</span></label>
                        <div class="bg-surface p-3 rounded border-light">
                            @foreach(\App\Models\Role::where('is_active', true)->get() as $role)
                                <div class="form-check mb-2">
                                    <input class="form-check-input edit-role-checkbox" type="checkbox" name="roles[]" value="{{ $role->id }}" id="edit-role-{{ $role->id }}">
                                    <label class="form-check-label" for="edit-role-{{ $role->id }}">
                                        <strong>{{ $role->display_name }}</strong>
                                        <p class="text-muted small mb-0">{{ $role->description }}</p>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" form="editUserForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>
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

        // Toggle edit password visibility
        const toggleEditPassword = document.getElementById('toggleEditPassword');
        const editPassword = document.getElementById('edit_password');
        
        if (toggleEditPassword && editPassword) {
            toggleEditPassword.addEventListener('click', function() {
                const type = editPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                editPassword.setAttribute('type', type);
                // Toggle the eye icon
                toggleEditPassword.querySelector('i').classList.toggle('fa-eye');
                toggleEditPassword.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // View User Modal
        const viewUserBtns = document.querySelectorAll('.view-user-btn');
        viewUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const userEmail = this.getAttribute('data-user-email');
                const userDepartment = this.getAttribute('data-user-department');
                const userCreated = this.getAttribute('data-user-created');
                const userUpdated = this.getAttribute('data-user-updated');
                const userVerified = this.getAttribute('data-user-verified');
                const userRoles = JSON.parse(this.getAttribute('data-user-roles'));
                
                // Set values in the view modal
                document.getElementById('view-user-id').textContent = userId;
                document.getElementById('view-user-name').textContent = userName;
                document.getElementById('view-user-email').textContent = userEmail;
                document.getElementById('view-user-department').textContent = userDepartment;
                document.getElementById('view-user-created').textContent = userCreated;
                document.getElementById('view-user-updated').textContent = userUpdated;
                document.getElementById('view-user-verified').textContent = userVerified;
                
                // Set user roles
                const rolesContainer = document.getElementById('view-user-roles');
                rolesContainer.innerHTML = '';
                
                if (userRoles.length > 0) {
                    userRoles.forEach((role, index) => {
                        const roleDiv = document.createElement('div');
                        roleDiv.className = 'mb-2' + (index < userRoles.length - 1 ? ' border-bottom pb-2' : '');
                        
                        const roleBadge = document.createElement('span');
                        roleBadge.className = 'badge bg-primary';
                        roleBadge.textContent = role.display_name;
                        
                        const roleDesc = document.createElement('p');
                        roleDesc.className = 'text-muted small mt-1';
                        roleDesc.textContent = role.description;
                        
                        roleDiv.appendChild(roleBadge);
                        roleDiv.appendChild(roleDesc);
                        rolesContainer.appendChild(roleDiv);
                    });
                } else {
                    rolesContainer.innerHTML = '<p class="text-muted">ไม่มีบทบาทที่กำหนด</p>';
                }
                
                // Set the user ID for the edit button
                document.getElementById('viewToEditButton').setAttribute('data-user-id', userId);
            });
        });
        
        // Edit User Modal
        const editUserBtns = document.querySelectorAll('.edit-user-btn');
        editUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const userEmail = this.getAttribute('data-user-email');
                const userDepartment = this.getAttribute('data-user-department');
                const userRoleIds = JSON.parse(this.getAttribute('data-user-roles'));
                
                // Set values in the edit form
                document.getElementById('edit_name').value = userName;
                document.getElementById('edit_email').value = userEmail;
                document.getElementById('edit_department_id').value = userDepartment;
                
                // Clear password fields
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirmation').value = '';
                
                // Set form action
                document.getElementById('editUserForm').action = `/admin/users/${userId}`;
                
                // Reset all role checkboxes first
                document.querySelectorAll('.edit-role-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Set user roles
                userRoleIds.forEach(roleId => {
                    const checkbox = document.getElementById(`edit-role-${roleId}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            });
        });
        
        // View to Edit button
        document.getElementById('viewToEditButton').addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            // Close view modal and open edit modal
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewUserModal'));
            viewModal.hide();
            
            // Find and click the edit button for this user
            document.querySelector(`.edit-user-btn[data-user-id="${userId}"]`).click();
        });

        // Show modal if there are validation errors
        @if($errors->any())
            const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
            addUserModal.show();
        @endif
    });
</script>
@endpush 