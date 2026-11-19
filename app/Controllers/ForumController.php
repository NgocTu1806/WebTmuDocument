<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ForumPost;
use App\Models\ForumAnswer;
use App\Models\Faculty;
use App\Models\Subject;

class ForumController extends Controller
{
    private ForumPost $postModel;
    private ForumAnswer $answerModel;

    public function __construct()
    {
        $this->postModel   = new ForumPost();
        $this->answerModel = new ForumAnswer();
    }

    public function index(array $params = []): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;

        $filters = [
            'status'     => trim($_GET['status'] ?? 'all'),
            'faculty_id' => !empty($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : null,
            'subject_id' => !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : null,
            'query'      => trim($_GET['q'] ?? ''),
            'sort'       => trim($_GET['sort'] ?? 'newest'),
        ];

        $postsData = $this->postModel->getPaginated($page, $perPage, $filters);
        $faculties = (new Faculty())->getAllForAdmin();
        $subjects  = (new Subject())->getAllForAdmin();
        $stats     = $this->postModel->getStats();

        $this->view('forum.index', [
            'title'     => 'Diễn đàn Hỏi & Đáp Sinh viên — TMU Tài Liệu',
            'posts'     => $postsData,
            'filters'   => $filters,
            'faculties' => $faculties,
            'subjects'  => $subjects,
            'stats'     => $stats,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->requireLogin();

        $faculties = (new Faculty())->getAllForAdmin();
        $subjects  = (new Subject())->getAllForAdmin();

        $this->view('forum.create', [
            'title'     => 'Đặt câu hỏi mới — Diễn đàn TMU',
            'faculties' => $faculties,
            'subjects'  => $subjects,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $title     = trim($_POST['title'] ?? '');
        $content   = trim($_POST['content'] ?? '');
        $facultyId = !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
        $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;

        $errors = [];
        if (mb_strlen($title) < 10) {
            $errors['title'] = 'Tiêu đề câu hỏi phải có ít nhất 10 ký tự.';
        }
        if (mb_strlen($content) < 15) {
            $errors['content'] = 'Nội dung câu hỏi phải có ít nhất 15 ký tự để người khác hiểu rõ vấn đề.';
        }

        if (!empty($errors)) {
            flash('errors', serialize($errors));
            setOldInput($_POST);
            $this->redirect('/forum/create');
        }

        $status = isModerator() ? 'approved' : 'pending';

        $postId = (int)$this->postModel->create([
            'user_id'       => userId(),
            'faculty_id'    => $facultyId,
            'subject_id'    => $subjectId,
            'title'         => $title,
            'content'       => $content,
            'status'        => $status,
            'view_count'    => 0,
            'answers_count' => 0,
            'is_solved'     => 0,
        ]);

        if ($status === 'pending') {
            flash('success', 'Câu hỏi của bạn đã được gửi thành công và đang chờ Quản trị viên duyệt trước khi hiển thị công khai.');
        } else {
            flash('success', 'Câu hỏi của bạn đã được đăng thành công lên diễn đàn!');
        }
        $this->redirect('/forum/' . $postId);
    }

    public function show(array $params = []): void
    {
        $postId = (int)$params['id'];
        $post   = $this->postModel->getWithDetails($postId);

        if (!$post) {
            http_response_code(404);
            $this->view('errors.404', [], 'main');
            exit;
        }

        // Nếu bài viết chưa được duyệt hoặc bị từ chối: chỉ người đăng hoặc Quản trị viên / Kiểm duyệt viên mới được xem
        if (($post['status'] ?? 'approved') !== 'approved') {
            if (!auth() || (userId() !== (int)$post['user_id'] && !isModerator())) {
                http_response_code(404);
                $this->view('errors.404', [], 'main');
                exit;
            }
        } else {
            // Tăng lượt xem khi bài viết đã được duyệt công khai
            $this->postModel->incrementView($postId);
        }

        $answers = $this->answerModel->getByPost($postId);

        $this->view('forum.show', [
            'title'   => e($post['title']) . ' — Diễn đàn TMU',
            'post'    => $post,
            'answers' => $answers,
        ]);
    }

    public function approve(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        if (!isModerator()) {
            flash('error', 'Bạn không có quyền duyệt câu hỏi.');
            $this->redirect('/forum');
        }

        $postId = (int)$params['id'];
        $post   = $this->postModel->findById($postId);

        if (!$post || !empty($post['deleted_at'])) {
            flash('error', 'Không tìm thấy câu hỏi.');
            $this->redirect('/forum');
        }

        $this->postModel->approve($postId);

        // Send notification to author
        (new \App\Models\Notification())->send(
            (int)$post['user_id'],
            'approved',
            'Câu hỏi diễn đàn đã được duyệt',
            'Câu hỏi "' . $post['title'] . '" của bạn đã được duyệt và hiển thị công khai trên diễn đàn.',
            '/forum/' . $postId
        );

        flash('success', 'Đã duyệt câu hỏi thành công! Bài viết đã được hiển thị công khai trên diễn đàn.');
        $this->redirect('/forum/' . $postId);
    }

    public function storeAnswer(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $postId  = (int)$params['id'];
        $post    = $this->postModel->findById($postId);

        if (!$post || !empty($post['deleted_at']) || ($post['status'] ?? 'approved') !== 'approved') {
            flash('error', 'Câu hỏi không tồn tại hoặc chưa được phê duyệt để thảo luận.');
            $this->redirect('/forum');
        }

        $content = trim($_POST['content'] ?? '');
        if (mb_strlen($content) < 5) {
            flash('error', 'Nội dung câu trả lời quá ngắn (tối thiểu 5 ký tự).');
            $this->redirect('/forum/' . $postId . '#answerForm');
        }

        $this->answerModel->create([
            'post_id' => $postId,
            'user_id' => userId(),
            'content' => $content,
        ]);

        $this->postModel->updateAnswerCount($postId);

        flash('success', 'Bạn đã gửi câu trả lời thành công!');
        $this->redirect('/forum/' . $postId . '#answers');
    }

    public function toggleSolve(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $postId = (int)$params['id'];
        $post   = $this->postModel->findById($postId);

        if (!$post) {
            flash('error', 'Không tìm thấy câu hỏi.');
            $this->redirect('/forum');
        }

        // Chỉ chủ câu hỏi hoặc Admin/Moderator mới có quyền đổi trạng thái
        if (userId() !== (int)$post['user_id'] && !isModerator()) {
            flash('error', 'Bạn không có quyền thay đổi trạng thái câu hỏi này.');
            $this->redirect('/forum/' . $postId);
        }

        $newStatus = (int)$post['is_solved'] === 1 ? 0 : 1;
        $this->postModel->toggleSolved($postId, $newStatus);

        flash('success', $newStatus === 1 ? 'Đã đánh dấu câu hỏi là ĐÃ GIẢI QUYẾT!' : 'Đã mở lại câu hỏi!');
        $this->redirect('/forum/' . $postId);
    }

    public function setBestAnswer(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $postId   = (int)$params['id'];
        $answerId = (int)$params['answer_id'];
        $post     = $this->postModel->findById($postId);

        if (!$post) {
            flash('error', 'Không tìm thấy câu hỏi.');
            $this->redirect('/forum');
        }

        // Chỉ chủ câu hỏi mới chọn được câu trả lời hay nhất
        if (userId() !== (int)$post['user_id']) {
            flash('error', 'Chỉ người đặt câu hỏi mới có quyền chọn câu trả lời chuẩn nhất.');
            $this->redirect('/forum/' . $postId);
        }

        $this->answerModel->setBestAnswer($postId, $answerId);

        flash('success', 'Đã cập nhật câu trả lời chuẩn nhất cho câu hỏi!');
        $this->redirect('/forum/' . $postId . '#answer-' . $answerId);
    }

    public function destroy(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $postId = (int)$params['id'];
        $post   = $this->postModel->findById($postId);

        if ($post) {
            if (userId() === (int)$post['user_id'] || isModerator()) {
                $this->postModel->softDelete($postId);
                flash('success', 'Câu hỏi đã được xóa.');
            } else {
                flash('error', 'Bạn không có quyền xóa câu hỏi này.');
            }
        }

        $this->redirect('/forum');
    }

    public function destroyAnswer(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $answerId = (int)$params['id'];
        $answer   = $this->answerModel->findById($answerId);

        if ($answer) {
            if (userId() === (int)$answer['user_id'] || isModerator()) {
                $this->answerModel->softDelete($answerId);
                $this->postModel->updateAnswerCount((int)$answer['post_id']);
                flash('success', 'Câu trả lời đã được xóa.');
                $this->redirect('/forum/' . $answer['post_id']);
            } else {
                flash('error', 'Bạn không có quyền xóa câu trả lời này.');
                $this->redirect('/forum/' . $answer['post_id']);
            }
        }

        $this->redirect('/forum');
    }
}
