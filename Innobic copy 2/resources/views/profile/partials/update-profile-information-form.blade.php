<section>
    <header>
        <h2 class="fs-5 fw-bold text-dark mb-1">
            ข้อมูลโปรไฟล์
        </h2>

        <p class="text-muted small">
            อัปเดตข้อมูลโปรไฟล์และอีเมลของคุณ
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-4">
            <label for="name" class="form-label">ชื่อ</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            </div>
            @error('name')
                <div class="invalid-feedback mt-1">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="form-label">อีเมล</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
            </div>
            @error('email')
                <div class="invalid-feedback mt-1">
                    {{ $message }}
                </div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <p class="mb-0">อีเมลของคุณยังไม่ได้รับการยืนยัน</p>
                            <button form="send-verification" class="btn btn-link text-primary p-0 mt-1 text-decoration-underline">
                                คลิกที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง
                            </button>
                        </div>
                    </div>

                    @if (session('status') === 'verification-link-sent')
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div>
                                ลิงก์ยืนยันใหม่ได้ถูกส่งไปยังอีเมลของคุณแล้ว
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-end mt-4">
            @if (session('status') === 'profile-updated')
                <div id="profile-status" class="alert alert-success py-2 px-3 mb-0 me-3 d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i> 
                    <span>บันทึกแล้ว</span>
                </div>
                
                <script>
                    setTimeout(() => document.getElementById('profile-status').remove(), 3000)
                </script>
            @endif
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> บันทึกข้อมูล
            </button>
        </div>
    </form>
</section>
