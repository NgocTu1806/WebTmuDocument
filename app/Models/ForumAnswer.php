<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ForumAnswer extends Model
{
    protected static string $table = 'forum_answers';

    public function getByPost(int $postId): array
    {
        $sql = "SELECT a.*, 
                       u.username, u.full_name, u.avatar, u.role AS user_role, u.student_id
                FROM " . static::$table . " a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.post_id = ? AND a.deleted_at IS NULL
                ORDER BY a.is_best_answer DESC, a.created_at ASC";

        return $this->db->fetchAll($sql, [$postId]);
    }

    public function setBestAnswer(int $postId, int $answerId): bool
    {
        // Check current status
        $current = $this->db->fetch(
            "SELECT is_best_answer FROM " . static::$table . " WHERE id = ? AND post_id = ?",
            [$answerId, $postId]
        );
        if (!$current) {
            return false;
        }

        $newStatus = $current['is_best_answer'] ? 0 : 1;

        // Reset any other best answer for this post if setting new one
        if ($newStatus === 1) {
            $this->db->query(
                "UPDATE " . static::$table . " SET is_best_answer = 0 WHERE post_id = ?",
                [$postId]
            );
        }

        $this->update($answerId, ['is_best_answer' => $newStatus]);

        // Also update post's solved status accordingly
        (new ForumPost())->update($postId, ['is_solved' => $newStatus]);

        return true;
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}
