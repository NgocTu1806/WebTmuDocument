<?php declare(strict_types=1); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= url('/') ?>">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('/forum') ?>">Diễn đàn</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Đặt câu hỏi mới</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 fs-3">
                            <i class="bi bi-question-diamond-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1">Đặt câu hỏi học tập</h3>
                            <p class="text-muted small mb-0">Mô tả câu hỏi chi tiết để các bạn sinh viên TMU có thể hỗ trợ bạn nhanh nhất</p>
                        </div>
                    </div>

                    <form action="<?= url('/forum') ?>" method="POST">
                        <?= csrfField() ?>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">
                                Tiêu đề câu hỏi <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="Ví dụ: Xin tài liệu ôn tập và đề cương môn Nguyên lý kế toán kỳ này..." 
                                   value="<?= old('title') ?>" required autofocus>
                            <div class="form-text">Tóm tắt ngắn gọn câu hỏi của bạn (tối thiểu 10 ký tự).</div>
                        </div>

                        <!-- Faculty & Subject selector -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="faculty_id" class="form-label fw-semibold">Khoa / Viện phụ trách</label>
                                <select class="form-select" id="faculty_id" name="faculty_id">
                                    <option value="">-- Chọn Khoa / Viện (Không bắt buộc) --</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= old('faculty_id') == $f['id'] ? 'selected' : '' ?>>
                                            <?= e($f['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subject_id" class="form-label fw-semibold">Môn học liên quan</label>
                                <select class="form-select" id="subject_id" name="subject_id">
                                    <option value="">-- Chọn Môn học (Không bắt buộc) --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-faculty="<?= $s['faculty_id'] ?? '' ?>" <?= old('subject_id') == $s['id'] ? 'selected' : '' ?>>
                                            <?= e($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="mb-4">
                            <label for="content" class="form-label fw-semibold">
                                Nội dung chi tiết <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="content" name="content" rows="6" 
                                      placeholder="Hãy mô tả chi tiết bài tập, câu hỏi hoặc thắc mắc của bạn (bạn đã làm được đến đâu, phần nào chưa hiểu rõ)..." required><?= old('content') ?></textarea>
                            <div class="form-text">Càng cung cấp chi tiết, bạn sẽ càng nhận được câu trả lời chính xác và nhanh chóng.</div>
                        </div>

                        <!-- Guidelines Alert -->
                        <div class="alert alert-light border small text-muted mb-3">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Gợi ý:</strong> Bạn có thể chọn Khoa hoặc Môn học liên quan để các bạn cùng lớp/khoa dễ dàng tìm thấy và tham gia thảo luận.
                        </div>

                        <!-- Moderation Note -->
                        <div class="alert alert-warning-subtle bg-warning bg-opacity-10 border border-warning-subtle small text-dark mb-4">
                            <i class="bi bi-shield-check text-warning me-2 fs-6"></i>
                            <strong>Kiểm duyệt nội dung:</strong> Câu hỏi của bạn sẽ được Quản trị viên duyệt trước khi hiển thị công khai trên diễn đàn.
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= url('/forum') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="bi bi-send me-1"></i>Đăng câu hỏi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Filter subjects when faculty changes
document.getElementById('faculty_id')?.addEventListener('change', function() {
    const facultyId = this.value;
    const subjectSelect = document.getElementById('subject_id');
    const options = subjectSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) return; // Skip placeholder
        const optFaculty = opt.getAttribute('data-faculty');
        if (!facultyId || optFaculty === facultyId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });

    // Reset selection if current selected is hidden
    const currentOpt = subjectSelect.querySelector(`option[value="${subjectSelect.value}"]`);
    if (currentOpt && currentOpt.style.display === 'none') {
        subjectSelect.value = '';
    }
});
</script>
