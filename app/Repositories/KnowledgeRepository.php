<?php
namespace App\Repositories;

use App\Helpers\Database;

class KnowledgeRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function categories(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*,
                    COUNT(a.id) as article_count,
                    SUM(CASE WHEN a.is_faq=1 AND a.status='published' AND a.deleted_at IS NULL THEN 1 ELSE 0 END) as faq_count
             FROM knowledge_categories c
             LEFT JOIN knowledge_articles a ON a.category_id = c.id AND a.deleted_at IS NULL
             WHERE c.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public function createCategory(array $data): int
    {
        return $this->db->insert('knowledge_categories', $data);
    }

    public function updateCategory(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('knowledge_categories', $data, 'id = ?', [$id]);
    }

    public function articles(array $filters = [], int $limit = 50): array
    {
        $sql = $this->articleSelect() . " WHERE a.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND a.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        if (isset($filters['is_faq']) && $filters['is_faq'] !== '') {
            $sql .= " AND a.is_faq = ?";
            $params[] = (int)$filters['is_faq'];
        }
        if (!empty($filters['article_type'])) {
            $sql .= " AND a.article_type = ?";
            $params[] = $filters['article_type'];
        }

        $sql .= " ORDER BY a.is_featured DESC, a.views DESC, a.updated_at DESC LIMIT {$limit}";
        return $this->db->fetchAll($sql, $params);
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->db->fetch($this->articleSelect() . " WHERE a.slug = ? AND a.deleted_at IS NULL", [$slug]);
    }

    public function findRawBySlug(string $slug): ?object
    {
        return $this->db->fetch("SELECT * FROM knowledge_articles WHERE slug = ? AND deleted_at IS NULL", [$slug]);
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM knowledge_articles WHERE slug = ? AND deleted_at IS NULL";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (bool)$this->db->fetch($sql, $params);
    }

    public function createArticle(array $data): int
    {
        return $this->db->insert('knowledge_articles', $data);
    }

    public function updateArticle(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('knowledge_articles', $data, 'id = ?', [$id]);
    }

    public function incrementViews(int $id, int $views): void
    {
        $this->db->update('knowledge_articles', ['views' => $views + 1], 'id = ?', [$id]);
    }

    public function attachments(int $articleId): array
    {
        return $this->db->fetchAll(
            "SELECT ka.*, TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as uploaded_by_name
             FROM knowledge_attachments ka
             JOIN users u ON ka.uploaded_by = u.id
             WHERE ka.article_id = ? AND ka.deleted_at IS NULL
             ORDER BY ka.created_at DESC",
            [$articleId]
        );
    }

    public function addAttachment(array $data): int
    {
        return $this->db->insert('knowledge_attachments', $data);
    }

    public function searchFullText(string $query, int $limit = 20): array
    {
        return $this->db->fetchAll(
            $this->articleSelect() . "
             WHERE a.status='published' AND a.deleted_at IS NULL
             AND MATCH(a.title, a.excerpt, a.content, a.tags) AGAINST (? IN BOOLEAN MODE)
             ORDER BY MATCH(a.title, a.excerpt, a.content, a.tags) AGAINST (? IN BOOLEAN MODE) DESC, a.views DESC
             LIMIT {$limit}",
            [$query, $query]
        );
    }

    public function searchLike(string $query, int $limit = 20): array
    {
        $needle = '%' . $query . '%';
        return $this->db->fetchAll(
            $this->articleSelect() . "
             WHERE a.status='published' AND a.deleted_at IS NULL
             AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ? OR a.tags LIKE ? OR c.name LIKE ?)
             ORDER BY a.views DESC, a.updated_at DESC
             LIMIT {$limit}",
            [$needle, $needle, $needle, $needle, $needle]
        );
    }

    private function articleSelect(): string
    {
        return "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color, c.icon as category_icon,
                       TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as author_name
                FROM knowledge_articles a
                LEFT JOIN knowledge_categories c ON a.category_id = c.id
                JOIN users u ON a.author_id = u.id";
    }
}
