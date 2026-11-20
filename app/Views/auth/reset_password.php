<?php declare(strict_types=1); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-2 text-center">Đặt lại mật khẩu</h3>
                    <p class="text-muted text-center small mb-4">Nhập mật khẩu mới cho tài khoản của bạn</p>

                    <form action="<?= url('/reset-password') ?>" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? ($_GET['token'] ?? '')) ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và số" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                                   placeholder="Nhập lại mật khẩu mới" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                            Xác nhận đặt lại mật khẩu
                        </button>
                    </form>

                    <hr>
                    <p class="text-center mb-0">
                        <a href="<?= url('/login') ?>" class="text-decoration-none">Quay lại đăng nhập</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
