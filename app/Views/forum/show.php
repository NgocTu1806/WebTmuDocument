<?php declare(strict_types=1); ?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/forum') ?>">Diễn đàn</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e(truncate($post['title'], 45)) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <?php if (($post['status'] ?? 'approved') === 'pending'): ?>
                <div class="alert alert-warning border-0 shadow-sm p-4 mb-4 rounded-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-clock-history fs-1 text-warning"></i>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Câu hỏi đang chờ duyệt</h5>
                                <p class="text-muted small mb-0">
                                    <?php if (isModerator()): ?>
                                        Bài đăng này chưa được công khai. Bạn có quyền phê duyệt hoặc từ chối bài đăng này.
                                    <?php else: ?>
                                        Câu hỏi của bạn đã được gửi thành công và đang chờ Quản trị viên phê duyệt trước khi hiển thị công khai.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if (isModerator()): ?>
                            <div class="d-flex gap-2">
                                <form action="<?= url('/admin/forum/' . $post['id'] . '/approve') ?>" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-success fw-bold btn-sm shadow-sm">
                                        <i class="bi bi-check-lg me-1"></i>Duyệt ngay
                                    </button>
                                </form>
                                <form action="<?= url('/admin/forum/' . $post['id'] . '/reject') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn từ chối bài viết này?');">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-lg me-1"></i>Từ chối
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (($post['status'] ?? 'approved') === 'rejected'): ?>
                <div class="alert alert-danger border-0 shadow-sm p-4 mb-4 rounded-3 d-flex align-items-center gap-3">
                    <i class="bi bi-x-octagon fs-1 text-danger"></i>
                    <div>
                        <h5 class="fw-bold text-danger mb-1">Câu hỏi đã bị từ chối</h5>
                        <p class="text-muted small mb-0">Bài đăng này không phù hợp với quy định diễn đàn nên đã bị từ chối phê duyệt.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Question Box -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <!-- Badges & Subject/Faculty Info -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <?php if (!empty($post['subject_name'])): ?>
                                <span class="badge bg-light text-primary border fs-6 px-3 py-2">
                                    <i class="bi bi-book me-1"></i><?= e($post['subject_name']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($post['faculty_name'])): ?>
                                <span class="badge bg-light text-muted border fs-6 px-3 py-2">
                                    <?= e($post['faculty_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions for Question Owner / Moderator -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if (auth() && (userId() === (int)$post['user_id'] || isModerator())): ?>
                                <form action="<?= url('/forum/' . $post['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này không?');">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa câu hỏi">
                                        <i class="bi bi-trash me-1"></i>Xóa câu hỏi
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Question Title -->
                    <h3 class="fw-bold mb-3"><?= e($post['title']) ?></h3>

                    <!-- Author Info & Meta -->
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">
                            <?= strtoupper(mb_substr($post['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark"><?= e($post['full_name'] ?? 'Sinh viên') ?></span>
                                <?php if (!empty($post['user_role']) && $post['user_role'] !== 'member'): ?>
                                    <span class="badge bg-danger small"><?= e($post['user_role']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">
                                <span><i class="bi bi-clock me-1"></i>Đăng lúc <?= formatDate($post['created_at'], 'H:i:s d/m/Y') ?></span>
                                <span class="mx-2">&bull;</span>
                                <span><i class="bi bi-chat-dots me-1"></i><?= count($answers) ?> câu trả lời</span>
                                <span class="mx-2">&bull;</span>
                                <span><i class="bi bi-eye me-1"></i><?= number_format((int)$post['view_count']) ?> lượt xem</span>
                            </div>
                        </div>
                    </div>

                    <!-- Question Body -->
                    <div class="post-content fs-6 lh-base mb-2" style="white-space: pre-line;">
                        <?= e($post['content']) ?>
                    </div>
                </div>
            </div>

            <!-- Answers Section -->
            <?php if (($post['status'] ?? 'approved') === 'approved'): ?>
            <div id="answers" class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-chat-left-text text-primary me-2"></i>Câu trả lời (<?= count($answers) ?>)
                    </h4>
                    <a href="#answerForm" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-reply me-1"></i>Viết câu trả lời
                    </a>
                </div>

                <?php if (empty($answers)): ?>
                    <div class="card border-0 shadow-sm p-4 text-center text-muted mb-4">
                        <i class="bi bi-chat-dots fs-2 mb-2"></i>
                        <p class="mb-0">Chưa có câu trả lời nào cho câu hỏi này. Bạn hãy là người đầu tiên chia sẻ câu trả lời nhé!</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($answers as $ans): ?>
                            <div id="answer-<?= $ans['id'] ?>" class="card shadow-sm border-0" style="border-radius: 10px;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                                <?= strtoupper(mb_substr($ans['full_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold"><?= e($ans['full_name'] ?? 'Sinh viên') ?></span>
                                                    <?php if ($ans['user_role'] === 'admin'): ?>
                                                        <span class="badge bg-danger small">Admin</span>
                                                    <?php elseif ($ans['user_role'] === 'moderator'): ?>
                                                        <span class="badge bg-warning text-dark small">Kiểm duyệt</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small" style="font-size: 0.8rem;">
                                                    <?= formatDate($ans['created_at'], 'H:i d/m/Y') ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Answer actions -->
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- Xóa câu trả lời (Tác giả câu trả lời hoặc Admin/Moderator) -->
                                            <?php if (auth() && (userId() === (int)$ans['user_id'] || isModerator())): ?>
                                                <form action="<?= url('/forum/answers/' . $ans['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa câu trả lời này?');">
                                                    <?= csrfField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa câu trả lời">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Answer Content -->
                                    <div class="answer-content fs-6 lh-base" style="white-space: pre-line;">
                                        <?= e($ans['content']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Answer Form Box -->
                <div id="answerForm" class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pencil-fill text-primary me-2"></i>Gửi câu trả lời của bạn</h5>

                    <?php if (auth()): ?>
                        <form action="<?= url('/forum/' . $post['id'] . '/answers') ?>" method="POST">
                            <?= csrfField() ?>
                            <div class="mb-3">
                                <textarea name="content" class="form-control" rows="5" placeholder="Chia sẻ lời giải, hướng dẫn hoặc kinh nghiệm của bạn về câu hỏi này..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-send me-1"></i>Đăng câu trả lời
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-light border text-center p-4 mb-0">
                            <p class="mb-2">Bạn cần <strong>đăng nhập</strong> để tham gia trả lời và giúp đỡ bạn bè.</p>
                            <a href="<?= url('/login') ?>" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập ngay
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted mb-4">
                    <i class="bi bi-shield-lock fs-1 text-secondary mb-2"></i>
                    <h6 class="fw-bold text-dark">Khu vực trả lời đang khóa</h6>
                    <p class="small mb-0">Người dùng sẽ có thể gửi câu trả lời sau khi câu hỏi được Ban quản trị phê duyệt.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column / Sidebar -->
        <div class="col-lg-4">
            <!-- Question Author Card -->
            <div class="card border-0 shadow-sm p-4 mb-4 text-center">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto d-flex align-items-center justify-content-center fw-bold fs-3 mb-3" style="width: 64px; height: 64px;">
                    <?= strtoupper(mb_substr($post['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= e($post['full_name']) ?></h5>
                <p class="text-muted small mb-2">@<?= e($post['username']) ?></p>
                <?php if (!empty($post['student_id'])): ?>
                    <span class="badge bg-light text-dark border mb-3">MSSV: <?= e($post['student_id']) ?></span>
                <?php endif; ?>
                <div class="border-top pt-3 small text-muted">
                    Tham gia diễn đàn hỏi đáp học tập TMU
                </div>
            </div>

            <!-- Helpful tips -->
            <div class="card border-0 shadow-sm p-3 mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Lời khuyên khi trả lời</h6>
                <ul class="text-muted small mb-0 ps-3">
                    <li class="mb-1">Giải thích chi tiết các bước làm thay vì chỉ đưa ra đáp án.</li>
                    <li class="mb-1">Gợi ý thêm tài liệu hoặc đề thi liên quan để bạn bè tham khảo.</li>
                    <li>Sử dụng ngôn từ văn minh, lịch sự, tôn trọng lẫn nhau.</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="<?= url('/forum') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Xem tất cả câu hỏi khác
                </a>
            </div>
        </div>
    </div>
</div>
