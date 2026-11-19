<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart text-primary me-2"></i>Thống kê hệ thống TMU</h4>
        <p class="text-muted small mb-0">Báo cáo tổng hợp số liệu người dùng, tài liệu và hoạt động tải xuống</p>
    </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small">Tổng người dùng</div>
            <div class="fs-3 fw-bold text-primary"><?= number_format($userStats['total']) ?></div>
            <div class="small text-success">+<?= $userStats['this_month'] ?> tháng này</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small">Tài liệu công khai</div>
            <div class="fs-3 fw-bold text-success"><?= number_format($documentStats['approved']) ?></div>
            <div class="small text-warning"><?= $documentStats['pending'] ?> chờ duyệt</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small">Lượt tải tài liệu</div>
            <div class="fs-3 fw-bold text-info"><?= number_format($totalDownloads) ?></div>
            <div class="small text-muted">Lịch sử lượt tải thực tế</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small">Khoa & Môn học</div>
            <div class="fs-3 fw-bold text-warning"><?= $totalFaculties ?> Khoa</div>
            <div class="small text-muted"><?= $totalSubjects ?> môn học</div>
        </div>
    </div>
</div>

<!-- Thống kê người dùng theo Ngày / Tháng / Năm -->
<div class="card border-0 shadow-sm p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>Thống kê người dùng theo Ngày / Tháng / Năm
        </h5>
        <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2">
            Đăng ký mới: <strong><?= number_format($filteredUserCount) ?></strong> thành viên (<?= e($filterLabel) ?>)
        </span>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="<?= url('/admin/statistics') ?>" class="row g-2 align-items-end mb-3 bg-light p-3 rounded">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Chế độ lọc:</label>
            <select name="filter_type" id="filterTypeSelect" class="form-select form-select-sm" onchange="toggleFilterInputs(this.value)">
                <option value="day" <?= $filterType === 'day' ? 'selected' : '' ?>>Theo ngày cụ thể</option>
                <option value="month" <?= $filterType === 'month' ? 'selected' : '' ?>>Theo tháng trong năm</option>
                <option value="year" <?= $filterType === 'year' ? 'selected' : '' ?>>Theo cả năm</option>
            </select>
        </div>

        <!-- Filter by Day input -->
        <div class="col-md-3 filter-input-group" id="groupDay" style="<?= $filterType === 'day' ? '' : 'display:none;' ?>">
            <label class="form-label small fw-semibold">Chọn ngày:</label>
            <input type="date" name="filter_date" class="form-control form-control-sm" value="<?= e($filterDate) ?>">
        </div>

        <!-- Filter by Month input -->
        <div class="col-md-2 filter-input-group" id="groupMonth" style="<?= $filterType === 'month' ? '' : 'display:none;' ?>">
            <label class="form-label small fw-semibold">Chọn tháng:</label>
            <select name="filter_month" class="form-select form-select-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filterMonth === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Filter by Year input -->
        <div class="col-md-2 filter-input-group" id="groupYear" style="<?= ($filterType === 'month' || $filterType === 'year') ? '' : 'display:none;' ?>">
            <label class="form-label small fw-semibold">Chọn năm:</label>
            <select name="filter_year" class="form-select form-select-sm">
                <?php
                $currentYear = (int)date('Y');
                for ($y = $currentYear; $y >= 2022; $y--):
                ?>
                    <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                <i class="bi bi-filter me-1"></i>Lọc dữ liệu
            </button>
        </div>
    </form>

    <!-- Table of Filtered Users -->
    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light sticky-top">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Họ và tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Mã sinh viên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                    <th class="text-end">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredUsers)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                            Không có người dùng nào đăng ký trong <strong><?= e($filterLabel) ?></strong>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredUsers as $idx => $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <span class="fw-semibold small"><?= e($u['full_name']) ?></span>
                            </td>
                            <td><span class="small text-muted">@<?= e($u['username']) ?></span></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td class="small fw-semibold"><?= e($u['student_id'] ?: '—') ?></td>
                            <td>
                                <?php
                                $roleBadge = match ($u['role']) {
                                    'admin'     => 'danger',
                                    'moderator' => 'warning text-dark',
                                    default     => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $roleBadge ?> small"><?= e($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success small">Hoạt động</span>
                                <?php elseif ($u['status'] === 'locked'): ?>
                                    <span class="badge bg-danger-subtle text-danger small">Đã khóa</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning small"><?= e($u['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= formatDate($u['created_at'], 'H:i:s d/m/Y') ?></td>
                            <td class="text-end">
                                <a href="<?= url('/admin/users/' . $u['id']) ?>" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Xem người dùng">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top 10 most downloaded documents -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-3 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-fire text-danger me-2"></i>Top 10 tài liệu được tải nhiều nhất</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tên tài liệu</th>
                            <th>Môn</th>
                            <th class="text-end">Lượt tải</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topDownloads)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                        <?php else: ?>
                            <?php foreach ($topDownloads as $idx => $d): ?>
                                <tr>
                                    <td><strong><?= $idx + 1 ?></strong></td>
                                    <td>
                                        <a href="<?= url('/documents/' . $d['id']) ?>" target="_blank" class="text-dark fw-semibold text-decoration-none small">
                                            <?= e(truncate($d['title'], 35)) ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?= e($d['subject_name'] ?? '') ?></td>
                                    <td class="text-end fw-bold text-success"><?= number_format((int)$d['download_count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top uploaders -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-3 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-trophy text-warning me-2"></i>Top thành viên tích cực đóng góp</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Thành viên</th>
                            <th>Số tài liệu</th>
                            <th class="text-end">Tổng lượt tải</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topUploaders)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                        <?php else: ?>
                            <?php foreach ($topUploaders as $idx => $u): ?>
                                <tr>
                                    <td><strong><?= $idx + 1 ?></strong></td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($u['full_name']) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">@<?= e($u['username']) ?></div>
                                    </td>
                                    <td class="fw-bold text-primary"><?= (int)$u['doc_count'] ?></td>
                                    <td class="text-end fw-bold text-success"><?= number_format((int)$u['total_downloads']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Documents by Faculty -->
<div class="card border-0 shadow-sm p-3">
    <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart text-primary me-2"></i>Phân bố tài liệu theo Khoa / Viện</h5>
    <div class="row g-3">
        <?php foreach ($byFaculty as $f): ?>
            <div class="col-md-4 col-sm-6">
                <div class="border rounded p-3 bg-light">
                    <div class="fw-bold small"><?= e($f['faculty_name']) ?></div>
                    <div class="fs-4 fw-bold text-primary mt-1"><?= number_format((int)$f['doc_count']) ?> <span class="fs-6 text-muted font-normal">tài liệu</span></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleFilterInputs(type) {
    const groupDay   = document.getElementById('groupDay');
    const groupMonth = document.getElementById('groupMonth');
    const groupYear  = document.getElementById('groupYear');

    if (type === 'day') {
        groupDay.style.display   = '';
        groupMonth.style.display = 'none';
        groupYear.style.display  = 'none';
    } else if (type === 'month') {
        groupDay.style.display   = 'none';
        groupMonth.style.display = '';
        groupYear.style.display  = '';
    } else { // year
        groupDay.style.display   = 'none';
        groupMonth.style.display = 'none';
        groupYear.style.display  = '';
    }
}
</script>
