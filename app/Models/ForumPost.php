<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ForumPost extends Model
{
    protected static string $table = 'forum_posts';

    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = ["p.deleted_at IS NULL", "p.status = 'approved'"];
        $params = [];

        // Filter by solved status
        if (isset($filters['status'])) {
            if ($filters['status'] === 'solved') {
                $where[] = "p.is_solved = 1";
            } elseif ($filters['status'] === 'unsolved') {
                $where[] = "p.is_solved = 0";
            }
        }

        // Filter by Faculty
        if (!empty($filters['faculty_id'])) {
            $where[] = "p.faculty_id = ?";
            $params[] = (int)$filters['faculty_id'];
        }

        // Filter by Subject
        if (!empty($filters['subject_id'])) {
            $where[] = "p.subject_id = ?";
            $params[] = (int)$filters['subject_id'];
        }

        // Search query
        if (!empty($filters['query'])) {
            $where[] = "(p.title LIKE ? OR p.content LIKE ?)";
            $searchTerm = '%' . trim($filters['query']) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = match ($filters['sort'] ?? 'newest') {
            'most_answers' => 'p.answers_count DESC, p.created_at DESC',
            'most_views'   => 'p.view_count DESC, p.created_at DESC',
            'unanswered'   => 'p.answers_count ASC, p.created_at DESC',
            default        => 'p.created_at DESC',
        };

        // Total count
        $countSql = "SELECT COUNT(*) FROM " . static::$table . " p WHERE {$whereClause}";
        $total = (int)$this->db->fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, 
                       u.username, u.full_name, u.avatar, u.role AS user_role,
                       f.name AS faculty_name,
                       s.name AS subject_name
                FROM " . static::$table . " p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN faculties f ON p.faculty_id = f.id
                LEFT JOIN subjects s ON p.subject_id = s.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $items = $this->db->fetchAll($sql, $params);

        return [
            'items'        => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
            'per_page'     => $perPage,
        ];
    }

    public function getWithDetails(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       u.username, u.full_name, u.avatar, u.role AS user_role, u.student_id,
                       f.name AS faculty_name,
                       s.name AS subject_name
                FROM " . static::$table . " p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN faculties f ON p.faculty_id = f.id
                LEFT JOIN subjects s ON p.subject_id = s.id
                WHERE p.id = ? AND p.deleted_at IS NULL
                LIMIT 1";

        $res = $this->db->fetch($sql, [$id]);
        return $res ?: null;
    }

    public function incrementView(int $id): void
    {
        $this->db->query(
            "UPDATE " . static::$table . " SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }

    public function updateAnswerCount(int $id): void
    {
        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM forum_answers WHERE post_id = ? AND deleted_at IS NULL",
            [$id]
        );
        $this->update($id, ['answers_count' => $count]);
    }

    public function toggleSolved(int $id, int $status): bool
    {
        return $this->update($id, ['is_solved' => $status]);
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function getPending(int $page = 1, int $perPage = 10): array
    {
        $whereClause = "p.deleted_at IS NULL AND p.status = 'pending'";
        $countSql = "SELECT COUNT(*) FROM " . static::$table . " p WHERE {$whereClause}";
        $total = (int)$this->db->fetchColumn($countSql);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, 
                       u.username, u.full_name, u.avatar, u.role AS user_role,
                       f.name AS faculty_name,
                       s.name AS subject_name
                FROM " . static::$table . " p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN faculties f ON p.faculty_id = f.id
                LEFT JOIN subjects s ON p.subject_id = s.id
                WHERE {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $items = $this->db->fetchAll($sql);

        return [
            'items'        => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
            'per_page'     => $perPage,
        ];
    }

    public function getPendingCount(): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM " . static::$table . " WHERE deleted_at IS NULL AND status = 'pending'"
        );
    }

    public function approve(int $id): bool
    {
        return $this->update($id, ['status' => 'approved']);
    }

    public function reject(int $id): bool
    {
        return $this->update($id, ['status' => 'rejected']);
    }

    public function getStats(): array
    {
        $totalPosts = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM " . static::$table . " WHERE deleted_at IS NULL AND status = 'approved'"
        );
        $totalAnswers = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM forum_answers WHERE deleted_at IS NULL"
        );

        return [
            'total_posts'   => $totalPosts,
            'total_answers' => $totalAnswers,
        ];
    }
}
