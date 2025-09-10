<section>
    <header>
        <h2 class="fs-5 fw-bold text-dark mb-1">
            ลบบัญชีผู้ใช้
        </h2>

        <p class="text-muted small">
            เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณาดาวน์โหลดข้อมูลหรือสารสนเทศที่คุณต้องการเก็บไว้ก่อนลบบัญชีของคุณ
        </p>
    </header>

    <div class="mt-3">
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion-modal">
            <i class="fas fa-trash-alt me-1"></i> ลบบัญชีผู้ใช้
        </button>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="confirm-user-deletion-modal" tabindex="-1" aria-labelledby="confirm-user-deletion-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header bg-danger bg-opacity-10">
                        <h5 class="modal-title text-danger fw-bold" id="confirm-user-deletion-modal-label">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            คุณแน่ใจหรือไม่ที่จะลบบัญชีผู้ใช้?
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร
                        </div>
                        
                        <p class="mb-3">
                            กรุณาป้อนรหัสผ่านของคุณเพื่อยืนยันว่าคุณต้องการลบบัญชีของคุณอย่างถาวร
                        </p>

                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input id="password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="กรอกรหัสผ่านของคุณ" required>
                            </div>
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback d-block mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> ลบบัญชี
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
