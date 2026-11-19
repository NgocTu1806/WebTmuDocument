<?php declare(strict_types=1); ?>
<div class="container py-4">
    <!-- Forum Header -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #1565C0 0%, #0d47a1 100%);">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">
                        <i class="bi bi-chat-quote-fill me-1"></i>Cộng đồng sinh viên TMU
                    </span>
                    <h2 class="fw-bold mb-2">Diễn Đàn Hỏi &amp; Đáp Học Tập</h2>
                    <p class="mb-0 text-white-50 lead fs-6" style="max-width: 600px;">
                        Nơi giao lưu, giải đáp thắc mắc bài tập, chia sẻ kinh nghiệm học tập và ôn thi giữa các sinh viên Đại học Thương mại.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?= url('/forum/create') ?>" class="btn btn-warning btn-lg fw-bold px-4 shadow">
                        <i class="bi bi-plus-circle me-1"></i>Đặt câu hỏi mới
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Question List (Left Column) -->
        <div class="col-lg-8">
            <!-- Filter & Search Bar -->
            <div class="card border-0 shadow-sm p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-chat-dots text-primary me-2"></i>Danh sách câu hỏi
                    </h5>

                    <!-- Sort Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted text-nowrap">Sắp xếp:</span>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value">
                            <option value="<?= url('/forum?' . http_build_query(array_merge($filters, ['sort' => 'newest']))) ?>" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="<?= url('/forum?' . http_build_query(array_merge($filters, ['sort' => 'most_answers']))) ?>" <?= $filters['sort'] === 'most_answers' ? 'selected' : '' ?>>Nhiều trả lời</option>
                            <option value="<?= url('/forum?' . http_build_query(array_merge($filters, ['sort' => 'most_views']))) ?>" <?= $filters['sort'] === 'most_views' ? 'selected' : '' ?>>Nhiều lượt xem</option>
                        </select>
                    </div>
                </div>

                <!-- Search inside questions -->
                <form action="<?= url('/forum') ?>" method="GET" class="input-group input-group-sm">
                    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
                    <?php if (!empty($filters['faculty_id'])): ?>
                        <input type="hidden" name="faculty_id" value="<?= (int)$filters['faculty_id'] ?>">
                    <?php endif; ?>
                    <?php if (!empty($filters['subject_id'])): ?>
                        <input type="hidden" name="subject_id" value="<?= (int)$filters['subject_id'] ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control" placeholder="Tìm kiếm câu hỏi, bài tập, chủ đề..." value="<?= e($filters['query']) ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search me-1"></i>Tìm kiếm
                    </button>
                    <?php if (!empty($filters['query']) || !empty($filters['faculty_id']) || !empty($filters['subject_id'])): ?>
                        <a href="<?= url('/forum') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List of Questions -->
            <?php if (empty($posts['items'])): ?>
                <div class="card border-0 shadow-sm p-5 text-center text-muted">
                    <i class="bi bi-chat-square-dots fs-1 mb-2"></i>
                    <h5>Không tìm thấy câu hỏi nào!</h5>
                    <p class="small mb-3">Hãy là người đầu tiên đặt câu hỏi để nhận trợ giúp từ bạn bè.</p>
                    <div>
                        <a href="<?= url('/forum/create') ?>" class="btn btn-primary btn-sm fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i>Đặt câu hỏi ngay
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($posts['items'] as $post): ?>
                        <div class="card border-0 shadow-sm hover-shadow transition" style="border-radius: 10px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <?php if (!empty($post['subject_name'])): ?>
                                        <span class="badge bg-light text-dark border small">
                                            <i class="bi bi-book me-1"></i><?= e($post['subject_name']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($post['faculty_name'])): ?>
                                        <span class="badge bg-light text-muted border small d-none d-md-inline">
                                            <?= e($post['faculty_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h5 class="fw-bold mb-2">
                                    <a href="<?= url('/forum/' . $post['id']) ?>" class="text-dark text-decoration-none">
                                        <?= e($post['title']) ?>
                                    </a>
                                </h5>

                                <p class="text-muted small mb-3 lh-sm">
                                    <?= e(truncate(strip_tags($post['content']), 140)) ?>
                                </p>

                                <!-- Meta footer -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top small text-muted">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-dark"><?= e($post['full_name'] ?? 'Sinh viên') ?></span>
                                        <span>&bull;</span>
                                        <span><?= formatDate($post['created_at'], 'H:i d/m/Y') ?></span>
                                    </div>

                                    <div class="d-flex align-items-center gap-3">
                                        <span class="<?= (int)$post['answers_count'] > 0 ? 'text-primary fw-semibold' : '' ?>">
                                            <i class="bi bi-chat-dots me-1"></i><?= (int)$post['answers_count'] ?> câu trả lời
                                        </span>
                                        <span>
                                            <i class="bi bi-eye me-1"></i><?= number_format((int)$post['view_count']) ?> lượt xem
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($posts['last_page'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $posts['last_page']; $i++): ?>
                                <li class="page-item <?= $i === $posts['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/forum?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar (Right Column) -->
        <div class="col-lg-4">
            <!-- Ask question quick CTA -->
            <div class="card border-0 shadow-sm p-4 mb-4 text-center bg-light">
                <i class="bi bi-lightbulb text-warning fs-1 mb-2"></i>
                <h5 class="fw-bold mb-2">Bạn có thắc mắc bài học?</h5>
                <p class="text-muted small mb-3">Đừng ngần ngại đặt câu hỏi, các bạn sinh viên TMU sẽ hỗ trợ bạn giải đáp trong thời gian sớm nhất.</p>
                <a href="<?= url('/forum/create') ?>" class="btn btn-primary fw-bold w-100 shadow-sm">
                    <i class="bi bi-pencil-square me-1"></i>Đặt câu hỏi ngay
                </a>
            </div>

            <!-- Filter by Faculty -->
            <div class="card border-0 shadow-sm p-3 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3 text-primary me-2"></i>Lọc theo Khoa / Viện</h6>
                <div class="list-group list-group-flush small">
                    <a href="<?= url('/forum?' . http_build_query(array_merge($filters, ['faculty_id' => '', 'page' => 1]))) ?>" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= empty($filters['faculty_id']) ? 'fw-bold text-primary active bg-light border-0' : '' ?>">
                        Tất cả các Khoa
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <?php foreach ($faculties as $f): ?>
                        <a href="<?= url('/forum?' . http_build_query(array_merge($filters, ['faculty_id' => $f['id'], 'page' => 1]))) ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= (!empty($filters['faculty_id']) && (int)$filters['faculty_id'] === (int)$f['id']) ? 'fw-bold text-primary active bg-light border-0' : '' ?>">
                            <?= e($f['name']) ?>
                            <i class="bi bi-chevron-right small text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Forum Rules / Guidelines -->
            <div class="card border-0 shadow-sm p-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-2"></i>Nội quy diễn đàn</h6>
                <ul class="text-muted small mb-0 ps-3">
                    <li class="mb-1">Đặt tiêu đề ngắn gọn, đúng trọng tâm câu hỏi.</li>
                    <li class="mb-1">Mô tả rõ đề bài hoặc phần kiến thức bạn chưa hiểu.</li>
                    <li class="mb-1">Bài đăng sẽ được duyệt trước khi hiển thị công khai.</li>
                    <li>Tôn trọng ý kiến bạn bè, không dùng ngôn từ xúc phạm.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
