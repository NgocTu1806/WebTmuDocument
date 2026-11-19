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
            <!-- Question Box -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <!-- Badges & Status -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <?php if ((int)$post['is_solved'] === 1): ?>
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="bi bi-check2-circle me-1"></i>Đã giải quyết
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                    <i class="bi bi-question-circle me-1"></i>Đang chờ giải đáp
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($post['subject_name'])): ?>
                                <span class="badge bg-light text-primary border fs-6 px-2.5 py-2">
                                    <i class="bi bi-book me-1"></i><?= e($post['subject_name']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($post['faculty_name'])): ?>
                                <span class="badge bg-light text-muted border fs-6 px-2.5 py-2">
                                    <?= e($post['faculty_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions for Question Owner / Moderator -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if (auth() && (userId() === (int)$post['user_id'] || isModerator())): ?>
                                <form action="<?= url('/forum/' . $post['id'] . '/solve') ?>" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-sm <?= (int)$post['is_solved'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="Đổi trạng thái câu hỏi">
                                        <i class="bi <?= (int)$post['is_solved'] === 1 ? 'bi-arrow-counterclockwise' : 'bi-check-lg' ?> me-1"></i>
                                        <?= (int)$post['is_solved'] === 1 ? 'Mở lại câu hỏi' : 'Đánh dấu đã giải quyết' ?>
                                    </button>
                                </form>

                                <form action="<?= url('/forum/' . $post['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này không?');">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa câu hỏi">
                                        <i class="bi bi-trash"></i>
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
                            <?php $isBest = (int)$ans['is_best_answer'] === 1; ?>
                            <div id="answer-<?= $ans['id'] ?>" class="card shadow-sm <?= $isBest ? 'border-2 border-success bg-success-subtle bg-opacity-10' : 'border-0' ?>" style="border-radius: 10px;">
                                <div class="card-body p-4">
                                    <?php if ($isBest): ?>
                                        <div class="badge bg-success text-white mb-3 px-3 py-2 fs-6 shadow-sm">
                                            <i class="bi bi-patch-check-fill me-1"></i>⭐ Câu trả lời chuẩn nhất được người hỏi bình chọn
                                        </div>
                                    <?php endif; ?>

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
                                            <!-- Chọn làm câu trả lời hay nhất (Chỉ chủ câu hỏi) -->
                                            <?php if (auth() && userId() === (int)$post['user_id']): ?>
                                                <form action="<?= url('/forum/' . $post['id'] . '/answers/' . $ans['id'] . '/best') ?>" method="POST" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <button type="submit" class="btn btn-sm <?= $isBest ? 'btn-success' : 'btn-outline-success' ?>" title="<?= $isBest ? 'Hủy chọn câu trả lời hay nhất' : 'Chọn câu trả lời hay nhất' ?>">
                                                        <i class="bi bi-patch-check me-1"></i>
                                                        <?= $isBest ? 'Đã chọn câu trả lời này' : 'Chọn câu trả lời hay nhất' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

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
