<section>
    <header>
        <h2 class="fs-5 fw-bold text-dark mb-1">
            อัปเดตรหัสผ่าน
        </h2>

        <p class="text-muted small">
            ตรวจสอบให้แน่ใจว่าบัญชีของคุณใช้รหัสผ่านที่ยาวและสุ่มเพื่อความปลอดภัย
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-4">
            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('current_password')
                <div class="invalid-feedback mt-1">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">รหัสผ่านใหม่</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback mt-1">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">ยืนยันรหัสผ่านใหม่</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-check-double"></i></span>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback mt-1">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="d-flex justify-content-end mt-4">
            @if (session('status') === 'password-updated')
                <div id="password-status" class="alert alert-success py-2 px-3 mb-0 me-3 d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>บันทึกแล้ว</span>
                </div>
                <script>
                    setTimeout(() => document.getElementById('password-status').remove(), 3000)
                </script>
            @endif

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> บันทึกรหัสผ่าน
            </button>
        </div>
    </form>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                }
            });
        });
    });
</script>
@endpush
