<?php

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('mail') ?? [];
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->config['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->config['username'] ?? '';
        $mail->Password   = $this->config['password'] ?? '';
        
        $encryption = strtolower($this->config['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port    = (int)($this->config['port'] ?? 587);
        $mail->CharSet = 'UTF-8';

        $fromEmail = !empty($this->config['from_email']) ? $this->config['from_email'] : ($this->config['username'] ?? 'no-reply@tmu.edu.vn');
        $fromName  = $this->config['from_name'] ?? 'TMU Tài Liệu';
        $mail->setFrom($fromEmail, $fromName);

        return $mail;
    }

    /**
     * Gửi mã OTP xác thực
     */
    public function sendOtpEmail(string $toEmail, string $toName, string $otp, string $subject = 'Xác thực mã OTP'): bool
    {
        // Ghi log mã OTP vào file để hỗ trợ test/debug nhanh khi chưa cấu hình SMTP
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        @file_put_contents(
            $logDir . '/otp.log',
            sprintf("[%s] To: %s (%s) | Subject: %s | OTP: %s\n", date('Y-m-d H:i:s'), $toEmail, $toName, $subject, $otp),
            FILE_APPEND
        );

        // Nếu chưa cấu hình username/password, có thể return false và ghi log lỗi
        if (empty($this->config['username']) || empty($this->config['password'])) {
            error_log("[MailService] Chưa cấu hình MAIL_USERNAME hoặc MAIL_PASSWORD trong config/config.php. OTP test: " . $otp);
            // Nếu bạn muốn trong môi trường dev chưa có mail thì coi như thành công và xem mã ở storage/logs/otp.log:
            // return true;
            return false;
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = "[TMU Tài Liệu] {$subject}";

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <h2 style='color: #1565C0; text-align: center; margin-top: 0;'>Xác Thực Tài Khoản</h2>
                    <p>Xin chào <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                    <p>Bạn (hoặc ai đó) vừa yêu cầu thao tác <strong>" . htmlspecialchars($subject) . "</strong> trên hệ thống <em>TMU Tài Liệu</em>.</p>
                    <p>Mã xác thực OTP của bạn là:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <span style='display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #D32F2F; background: #f8f9fa; padding: 10px 24px; border: 2px dashed #1565C0; border-radius: 6px;'>
                            {$otp}
                        </span>
                    </div>
                    <p style='color: #555; font-size: 13px;'>Mã này có hiệu lực trong vòng <strong>60 giây</strong>. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #888; text-align: center; margin-bottom: 0;'>Hệ thống chia sẻ tài liệu học tập - Đại học Thương mại</p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("[MailService] Gửi mail thất bại: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi link đặt lại mật khẩu (quên mật khẩu)
     */
    public function sendResetLinkEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        if (empty($this->config['username']) || empty($this->config['password'])) {
            error_log("[MailService] Chưa cấu hình MAIL_USERNAME hoặc MAIL_PASSWORD. Reset link: " . $resetLink);
            return false;
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = '[TMU Tài Liệu] Yêu cầu đặt lại mật khẩu';

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                    <h2 style='color: #1565C0; margin-top: 0;'>Đặt lại mật khẩu</h2>
                    <p>Xin chào <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                    <p>Bạn đã gửi yêu cầu đặt lại mật khẩu. Vui lòng bấm vào nút bên dưới để tiến hành đổi mật khẩu mới (liên kết có hiệu lực trong 15 phút):</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$resetLink}' style='background: #1565C0; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Đặt lại mật khẩu</a>
                    </div>
                    <p style='font-size: 13px; color: #666;'>Hoặc copy liên kết sau vào trình duyệt:<br><a href='{$resetLink}'>{$resetLink}</a></p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("[MailService] Gửi mail reset thất bại: " . $e->getMessage());
            return false;
        }
    }
}
