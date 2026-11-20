<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\OtpService;

class AuthController extends Controller
{
    private AuthService $authService;
    private User $userModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel   = new User();
    }

    public function loginForm(array $params = []): void
    {
        if (auth()) {
            $this->redirect('/');
        }
        $this->view('auth.login', [
            'title' => 'Đăng nhập — TMU Tài Liệu',
        ]);
    }

    public function login(array $params = []): void
    {
        if (auth()) {
            $this->redirect('/');
        }

        $this->csrfValidate();

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/login');
        }

        $result = $this->authService->login($email, $password, $remember);

        if (!$result['success']) {
            storeOldInput();
            flash('error', $result['error']);
            $this->redirect('/login');
        }

        clearOldInput();
        $fullName = e(trim((string)($result['user']['full_name'] ?? '')));
        flash('success', 'Chào mừng bạn quay lại, ' . $fullName . '!');
        $this->redirect('/');
    }

    public function registerForm(array $params = []): void
    {
        if (auth()) {
            $this->redirect('/');
        }
        $this->view('auth.register', [
            'title' => 'Đăng ký tài khoản — TMU Tài Liệu',
        ]);
    }

    public function register(array $params = []): void
    {
        if (auth()) {
            $this->redirect('/');
        }

        $this->csrfValidate();

        // 1. Chuẩn hóa dữ liệu đầu vào
        $data = [
            'username'              => trim($_POST['username'] ?? ''),
            'email'                 => strtolower(trim($_POST['email'] ?? '')),
            'full_name'             => trim($_POST['full_name'] ?? ''),
            'password'              => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
            'student_id'            => strtoupper(trim($_POST['student_id'] ?? '')),
        ];

        // 2. Validate bằng bộ quy tắc trong Validator.php
        $errors = $this->validate($data, [
            'full_name'  => 'required|min:2|max:100|vietnamese_name',
            'student_id' => 'required|tmu_student_id',
            'email'      => 'required|email|tmu_student_email',
            'username'   => 'required|custom_username',
            'password'   => 'required|password_rules|confirmed',
        ]);

        $userModel = new User();

        // 3. Kiểm tra chống trùng lặp dữ liệu trong CSDL
        if (empty($errors['student_id']) && $userModel->studentIdExists($data['student_id'])) {
            $errors['student_id'][] = 'Mã sinh viên này đã được đăng ký trên hệ thống.';
        }
        if (empty($errors['username']) && $userModel->usernameExists($data['username'])) {
            $errors['username'][] = 'Tên đăng nhập này đã được sử dụng.';
        }
        if (empty($errors['email']) && $userModel->emailExists($data['email'])) {
            $errors['email'][] = 'Email này đã được sử dụng.';
        }

        // 4. Trả về lỗi nếu có
        if (!empty($errors)) {
            storeOldInput();
            $_SESSION['old_input']['student_id'] = $data['student_id'];
            $_SESSION['old_input']['email']      = $data['email'];
            $_SESSION['old_input']['username']   = $data['username'];
            $_SESSION['old_input']['full_name']  = $data['full_name'];
            flash('errors', serialize($errors));
            $this->redirect('/register');
        }

        // 5. Tạo tài khoản và đăng nhập ngay
        $result = $this->authService->register($data);

        if (!$result['success']) {
            storeOldInput();
            flash('errors', serialize($result['errors']));
            $this->redirect('/register');
        }

        clearOldInput();
        flash('success', 'Đăng ký thành công! Chào mừng bạn đến với TMU Tài Liệu.');
        $this->redirect('/');
    }

    public function logout(array $params = []): void
    {
        $this->csrfValidate();
        $this->authService->logout();
        flash('success', 'Bạn đã đăng xuất thành công.');
        $this->redirect('/login');
    }

    public function forgotForm(array $params = []): void
    {
        $this->view('auth.forgot_password', ['title' => 'Quên mật khẩu — TMU Tài Liệu']);
    }

    public function forgot(array $params = []): void
    {
        $this->csrfValidate();
        $email = strtolower(trim($_POST['email'] ?? ''));

        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);
        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/forgot-password');
        }

        $rawToken = $this->authService->sendPasswordReset($email);

        if (empty($rawToken)) {
            storeOldInput();
            flash('error', 'Địa chỉ email này không tồn tại trong hệ thống. Vui lòng kiểm tra lại!');
            $this->redirect('/forgot-password');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        $resetLink = url('/reset-password/' . $rawToken);

        $mailService = new MailService();
        $sent = $mailService->sendResetLinkEmail($email, $user['full_name'] ?? 'Sinh viên TMU', $resetLink);

        clearOldInput();
        if (!$sent) {
            flash('error', 'Không thể gửi email lúc này. Vui lòng kiểm tra cấu hình SMTP và thử lại sau.');
            $this->redirect('/forgot-password');
        }

        flash('success', 'Liên kết đặt lại mật khẩu (hiệu lực 1 giờ) đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư đến hoặc mục Spam.');
        $this->redirect('/forgot-password');
    }

    public function resetForm(array $params = []): void
    {
        $token = $params['token'] ?? '';
        $this->view('auth.reset_password', [
            'title' => 'Đặt lại mật khẩu — TMU Tài Liệu',
            'token' => $token,
        ]);
    }

    public function reset(array $params = []): void
    {
        $this->csrfValidate();
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $errors   = $this->validate(
            ['password' => $password, 'password_confirmation' => $_POST['password_confirmation'] ?? ''],
            ['password' => 'required|password_rules|confirmed']
        );

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/reset-password/' . urlencode($token));
        }

        $success = $this->authService->resetPassword($token, $password);
        if (!$success) {
            flash('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            $this->redirect('/forgot-password');
        }

        flash('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.');
        $this->redirect('/login');
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- 1. ĐĂNG KÝ VỚI OTP ---
    public function registerSendOtp(array $params = []): void
    {
        try {
            $this->csrfValidate();

            $data = [
                'username'              => trim($_POST['username'] ?? ''),
                'email'                 => strtolower(trim($_POST['email'] ?? '')),
                'full_name'             => trim($_POST['full_name'] ?? ''),
                'password'              => $_POST['password'] ?? '',
                'password_confirmation' => $_POST['password_confirmation'] ?? '',
                'student_id'            => strtoupper(trim($_POST['student_id'] ?? '')),
            ];

            $errors = $this->validate($data, [
                'full_name'  => 'required|min:2|max:100|vietnamese_name',
                'student_id' => 'required|tmu_student_id',
                'email'      => 'required|email|tmu_student_email',
                'username'   => 'required|custom_username',
                'password'   => 'required|password_rules|confirmed',
            ]);

            $userModel = new User();
            if (empty($errors['student_id']) && $userModel->studentIdExists($data['student_id'])) {
                $errors['student_id'][] = 'Mã sinh viên này đã được đăng ký trên hệ thống.';
            }
            if (empty($errors['username']) && $userModel->usernameExists($data['username'])) {
                $errors['username'][] = 'Tên đăng nhập này đã được sử dụng.';
            }
            if (empty($errors['email']) && $userModel->emailExists($data['email'])) {
                $errors['email'][] = 'Email này đã được sử dụng.';
            }

            if (!empty($errors)) {
                $firstFieldErrors = reset($errors);
                $this->jsonResponse(['success' => false, 'message' => $firstFieldErrors[0]]);
            }

            $otpService = new OtpService();
            $gen = $otpService->generate('register', $data);
            if (!$gen['success']) {
                $this->jsonResponse($gen);
            }

            $mailService = new MailService();
            if (!$mailService->sendOtpEmail($data['email'], $data['full_name'], $gen['otp'], 'Xác thực đăng ký')) {
                $this->jsonResponse(['success' => false, 'message' => 'Không thể gửi email OTP. Vui lòng kiểm tra cấu hình SMTP.']);
            }

            $this->jsonResponse([
                'success'    => true,
                'expires_in' => $gen['expires_in'],
                'message'    => "Mã OTP đã được gửi tới {$data['email']}."
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    public function registerVerifyOtp(array $params = []): void
    {
        try {
            $this->csrfValidate();

            $otpService = new OtpService();
            $verify = $otpService->verify('register', $_POST['otp'] ?? '');
            if (!$verify['success']) {
                $this->jsonResponse($verify);
            }

            $result = $this->authService->register($verify['payload']);
            if (!$result['success']) {
                $this->jsonResponse(['success' => false, 'message' => 'Không thể tạo tài khoản. Vui lòng thử lại.']);
            }

            clearOldInput();
            flash('success', 'Đăng ký thành công! Chào mừng bạn đến với TMU Tài Liệu.');
            $this->jsonResponse([
                'success'      => true,
                'message'      => 'Đăng ký thành công! Đang chuyển hướng...',
                'redirect_url' => url('/')
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    // --- 2. ĐỔI MẬT KHẨU VỚI OTP ---
    public function changePasswordSendOtp(array $params = []): void
    {
        try {
            if (empty($_SESSION['user']['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập lại.'], 401);
            }

            $this->csrfValidate();

            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirm     = $_POST['new_password_confirmation'] ?? '';

            $user = $this->userModel->findById((int)$_SESSION['user']['id']);
            if (!$user) {
                $this->jsonResponse(['success' => false, 'message' => 'Người dùng không tồn tại.']);
            }

            if (!password_verify($currentPass, $user['password_hash'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
            }

            $errors = $this->validate(
                ['password' => $newPass, 'password_confirmation' => $confirm],
                ['password' => 'required|password_rules|confirmed']
            );
            if (!empty($errors)) {
                $firstFieldErrors = reset($errors);
                $this->jsonResponse(['success' => false, 'message' => $firstFieldErrors[0]]);
            }

            $otpService = new OtpService();
            $gen = $otpService->generate('change_password', [
                'user_id'       => $user['id'],
                'password_hash' => password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12])
            ]);
            if (!$gen['success']) {
                $this->jsonResponse($gen);
            }

            $mailService = new MailService();
            if (!$mailService->sendOtpEmail($user['email'], $user['full_name'], $gen['otp'], 'Xác nhận đổi mật khẩu')) {
                $this->jsonResponse(['success' => false, 'message' => 'Không thể gửi email OTP. Vui lòng kiểm tra cấu hình SMTP.']);
            }

            $this->jsonResponse([
                'success'    => true,
                'expires_in' => $gen['expires_in'],
                'message'    => "Mã OTP đã được gửi tới {$user['email']}."
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    public function changePasswordVerifyOtp(array $params = []): void
    {
        try {
            $this->csrfValidate();

            $otpService = new OtpService();
            $verify = $otpService->verify('change_password', $_POST['otp'] ?? '');
            if (!$verify['success']) {
                $this->jsonResponse($verify);
            }

            $payload = $verify['payload'];
            $this->userModel->updatePassword((int)$payload['user_id'], $payload['password_hash']);

            flash('success', 'Đổi mật khẩu thành công!');
            $this->jsonResponse([
                'success'      => true,
                'message'      => 'Đổi mật khẩu thành công!',
                'redirect_url' => url('/profile/edit')
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}
