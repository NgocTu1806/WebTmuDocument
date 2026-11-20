<?php declare(strict_types=1); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-2 text-center">Quên mật khẩu</h3>
                    <p class="text-muted text-center small mb-4">Nhập email sinh viên TMU để nhận liên kết đặt lại mật khẩu</p>

                    <form action="<?= url('/forgot-password') ?>" method="POST">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email sinh viên TMU</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= old('email') ?>" placeholder="21d123456@mailsv.tmu.edu.vn" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                            Gửi liên kết đặt lại mật khẩu
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