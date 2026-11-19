<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Document;
use App\Models\Favorite;

class FavoriteController extends Controller
{
    public function toggle(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $docId = (int)$params['id'];
        $doc   = (new Document())->getWithDetails($docId);
        if (!$doc || $doc['status'] !== 'approved') {
            if (isAjax()) {
                $this->json(['error' => 'Tài liệu không tồn tại hoặc chưa được công khai.'], 404);
            }
            flash('error', 'Tài liệu không tồn tại hoặc chưa được công khai.');
            $this->redirect('/documents');
        }

        $favoriteModel = new Favorite();
        $added         = $favoriteModel->toggle(userId(), $docId);

        if (isAjax()) {
            $this->json(['favorited' => $added, 'docId' => $docId]);
        }

        flash('success', $added ? 'Đã lưu tài liệu thành công.' : 'Đã bỏ lưu tài liệu.');
        $this->redirect('/documents/' . $docId);
    }
}

