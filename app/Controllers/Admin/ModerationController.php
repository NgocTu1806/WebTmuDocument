<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Document;
use App\Models\ForumPost;
use App\Models\Notification;
use App\Services\FileService;

class ModerationController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $activeTab    = trim($_GET['tab'] ?? 'documents');
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 10;
        $docModel     = new Document();
        $forumModel   = new ForumPost();

        $docs         = $docModel->getPending($activeTab === 'documents' ? $page : 1, $perPage);
        $forumPosts   = $forumModel->getPending($activeTab === 'forum' ? $page : 1, $perPage);
        $pendingForum = $forumModel->getPendingCount();

        $this->view('admin.moderation.index', [
            'title'        => 'Kiểm duyệt nội dung — Admin TMU',
            'activeTab'    => $activeTab,
            'docs'         => $docs,
            'forumPosts'   => $forumPosts,
            'pendingForum' => $pendingForum,
        ], 'admin');
    }

    public function approve(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId    = (int)$params['id'];
        $docModel = new Document();
        $doc      = $docModel->findById($docId);

        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/moderation');
        }

        $fileService = new FileService();
        $fileName    = (string)($doc['file_path'] ?? '');
        $pendingFile = STORAGE_PATH . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'pending' . DIRECTORY_SEPARATOR . basename($fileName);
        $approvedFile = STORAGE_PATH . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'approved' . DIRECTORY_SEPARATOR . basename($fileName);
        $hasPhysicalFile = $fileName !== '' && (file_exists($pendingFile) || file_exists($approvedFile));

        if ($hasPhysicalFile) {
            $fileMoved = false;
            try {
                $fileMoved = $fileService->moveToApproved($fileName);
            } catch (\Throwable $e) {
                error_log("File move error approving document {$docId}: " . $e->getMessage());
            }

            if (!$fileMoved) {
                flash('error', 'Không thể di chuyển tệp tin sang thư mục storage/documents/approved. Vui lòng kiểm tra quyền ghi thư mục.');
                $this->redirect('/admin/moderation');
            }
        }

        // Cập nhật trạng thái CSDL sang approved và xóa lý do từ chối cũ (nếu có)
        try {
            $updated = $docModel->update($docId, [
                'status'           => 'approved',
                'rejection_reason' => null,
                'approved_by'      => userId(),
                'approved_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log("Database error approving document {$docId}: " . $e->getMessage());
            $updated = false;
        }

        if (!$updated) {
            flash('error', 'Cập nhật trạng thái tài liệu thất bại.');
            $this->redirect('/admin/moderation');
        }

        // Gửi thông báo cho người đăng
        (new Notification())->send(
            (int)$doc['user_id'],
            'approved',
            'Tài liệu được phê duyệt',
            'Tài liệu "' . $doc['title'] . '" của bạn đã được phê duyệt và hiển thị công khai.',
            '/documents/' . $docId
        );

        if ($hasPhysicalFile) {
            flash('success', 'Tài liệu đã được phê duyệt thành công.');
        } else {
            flash('warning', 'Tài liệu đã được phê duyệt (Lưu ý: tệp tin gốc chưa có trong thư mục storage/documents).');
        }

        $this->redirect('/admin/moderation');
    }

    public function reject(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId    = (int)$params['id'];
        $reason   = trim($_POST['reason'] ?? '');
        $docModel = new Document();
        $doc      = $docModel->findById($docId);

        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/moderation');
        }

        if ($reason === '' || mb_strlen($reason) > 1000) {
            flash('error', 'Vui lòng nhập lý do từ chối hợp lệ (tối đa 1000 ký tự).');
            $this->redirect('/admin/moderation');
        }

        $docModel->update($docId, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Notify uploader
        $notifModel = new Notification();
        $notifModel->send(
            (int)$doc['user_id'],
            'rejected',
            'Tài liệu bị từ chối',
            'Tài liệu "' . $doc['title'] . '" đã bị từ chối. Lý do: ' . $reason,
            '/my-documents'
        );

        flash('warning', 'Tài liệu đã bị từ chối.');
        $this->redirect('/admin/moderation');
    }

    public function hide(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId    = (int)$params['id'];
        $docModel = new Document();
        $doc      = $docModel->findById($docId);
        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/documents');
        }

        $docModel->update($docId, ['status' => 'hidden']);

        (new Notification())->send(
            (int)$doc['user_id'],
            'hidden',
            'Tài liệu tạm thời bị ẩn',
            'Tài liệu "' . $doc['title'] . '" của bạn đã được quản trị viên tạm ẩn khỏi danh sách công khai.',
            '/my-documents'
        );

        flash('info', 'Tài liệu đã được ẩn.');
        $this->redirect('/admin/documents');
    }

    public function unhide(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId    = (int)$params['id'];
        $docModel = new Document();
        $doc      = $docModel->findById($docId);
        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/documents');
        }

        if (!empty($doc['file_path'])) {
            (new FileService())->moveToApproved($doc['file_path']);
        }

        $docModel->update($docId, ['status' => 'approved']);

        (new Notification())->send(
            (int)$doc['user_id'],
            'approved',
            'Tài liệu được hiển thị trở lại',
            'Tài liệu "' . $doc['title'] . '" của bạn đã được hiển thị công khai trở lại.',
            '/documents/' . $docId
        );

        flash('success', 'Tài liệu đã được hiển thị trở lại.');
        $this->redirect('/admin/documents');
    }

    public function approveForumPost(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $postId     = (int)$params['id'];
        $forumModel = new ForumPost();
        $post       = $forumModel->findById($postId);

        if (!$post || !empty($post['deleted_at'])) {
            flash('error', 'Câu hỏi diễn đàn không tồn tại.');
            $this->redirect('/admin/moderation?tab=forum');
        }

        $forumModel->approve($postId);

        // Thông báo cho tác giả
        (new Notification())->send(
            (int)$post['user_id'],
            'approved',
            'Câu hỏi diễn đàn đã được duyệt',
            'Câu hỏi "' . $post['title'] . '" của bạn đã được duyệt và hiển thị công khai trên diễn đàn.',
            '/forum/' . $postId
        );

        flash('success', 'Câu hỏi diễn đàn đã được phê duyệt thành công!');

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (str_contains($referer, '/forum/' . $postId)) {
            $this->redirect('/forum/' . $postId);
        } else {
            $this->redirect('/admin/moderation?tab=forum');
        }
    }

    public function rejectForumPost(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $postId     = (int)$params['id'];
        $forumModel = new ForumPost();
        $post       = $forumModel->findById($postId);

        if (!$post || !empty($post['deleted_at'])) {
            flash('error', 'Câu hỏi diễn đàn không tồn tại.');
            $this->redirect('/admin/moderation?tab=forum');
        }

        $forumModel->reject($postId);

        // Thông báo cho tác giả
        (new Notification())->send(
            (int)$post['user_id'],
            'rejected',
            'Câu hỏi diễn đàn bị từ chối',
            'Câu hỏi "' . $post['title'] . '" của bạn không phù hợp với quy định diễn đàn và đã bị từ chối duyệt.',
            '/forum'
        );

        flash('warning', 'Câu hỏi diễn đàn đã bị từ chối.');

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (str_contains($referer, '/forum/' . $postId)) {
            $this->redirect('/forum/' . $postId);
        } else {
            $this->redirect('/admin/moderation?tab=forum');
        }
    }
}