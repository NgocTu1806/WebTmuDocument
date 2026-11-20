<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $mailCfg = Config::get('mail');
        if (!is_array($mailCfg) || empty($mailCfg)) {
            $config = defined('ROOT_PATH') && file_exists(ROOT_PATH . '/config/config.php')
                ? require ROOT_PATH . '/config/config.php'
                : [];
            $mailCfg = $config['mail'] ?? [];
        }

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $mailCfg['host'] ?? 'smtp.gmail.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $mailCfg['username'] ?? '';
        $this->mailer->Password   = $mailCfg['password'] ?? '';

        $encryption = strtolower($mailCfg['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $this->mailer->Port    = (int)($mailCfg['port'] ?? 587);
        $this->mailer->CharSet = 'UTF-8';

        $fromEmail = !empty($mailCfg['from_address'])
            ? $mailCfg['from_address']
            : (!empty($mailCfg['from_email']) ? $mailCfg['from_email'] : ($this->mailer->Username ?: 'no-reply@tmu.edu.vn'));
        $fromName  = $mailCfg['from_name'] ?? 'TMU Tài Liệu';
        $this->mailer->setFrom($fromEmail, $fromName);
    }

    public function sendOtpEmail(string $toEmail, string $fullName, string $otp, string $title = 'Xác thực OTP'): bool
    {
        // Ghi log mã OTP vào file để hỗ trợ test/debug nhanh khi chưa cấu hình SMTP
        $logDir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        @file_put_contents(
            $logDir . '/otp.log',
            sprintf("[%s] To: %s (%s) | Title: %s | OTP: %s\n", date('Y-m-d H:i:s'), $toEmail, $fullName, $title, $otp),
            FILE_APPEND
        );

        if (empty($this->mailer->Username) || empty($this->mailer->Password) || str_contains($this->mailer->Username, 'email_cua_ban')) {
            error_log("[MailService] Chưa cấu hình email gửi (username/password) trong config/config.php hoặc biến môi trường. Mã OTP test: " . $otp);
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($toEmail, $fullName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "[TMU Tài Liệu] {$title}: {$otp}";
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #1565C0; margin-top: 0;'>{$title}</h2>
                    <p>Xin chào <strong>{$fullName}</strong>,</p>
                    <p>Mã xác thực OTP của bạn là:</p>
                    <div style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #D32F2F; background: #F5F5F5; padding: 12px; text-align: center; border-radius: 6px;'>
                        {$otp}
                    </div>
                    <p style='color: #555; font-size: 13px; margin-top: 15px;'>
                        * Mã có hiệu lực trong vòng <strong>1 phút (60 giây)</strong>.<br>
                        * Tuyệt đối không chia sẻ mã này cho bất kỳ ai.
                    </p>
                </div>
            ";
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('MailService OTP Error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    public function sendResetLinkEmail(string $toEmail, string $fullName, string $resetLink): bool
    {
        $logDir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        @file_put_contents(
            $logDir . '/otp.log',
            sprintf("[%s] To: %s (%s) | ResetLink: %s\n", date('Y-m-d H:i:s'), $toEmail, $fullName, $resetLink),
            FILE_APPEND
        );

        if (empty($this->mailer->Username) || empty($this->mailer->Password) || str_contains($this->mailer->Username, 'email_cua_ban')) {
            error_log("[MailService] Chưa cấu hình email gửi (username/password). Link reset: " . $resetLink);
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($toEmail, $fullName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '[TMU Tài Liệu] Yêu cầu đặt lại mật khẩu';
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #1565C0; margin-top: 0;'>Đặt lại mật khẩu</h2>
                    <p>Xin chào <strong>{$fullName}</strong>,</p>
                    <p>Nhấn vào nút bên dưới để đặt lại mật khẩu cho tài khoản của bạn (liên kết có hiệu lực trong 1 giờ):</p>
                    <p style='text-align: center; margin: 24px 0;'>
                        <a href='{$resetLink}' style='background: #1565C0; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>
                            Đặt lại mật khẩu ngay
                        </a>
                    </p>
                    <p style='font-size: 12px; color: #777;'>Nếu nút không hoạt động, copy đường dẫn sau vào trình duyệt:<br>{$resetLink}</p>
                </div>
            ";
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('MailService ResetLink Error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
