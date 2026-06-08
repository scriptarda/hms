<?php
namespace App\Services;

use App\Repositories\KnowledgeRepository;

class KnowledgeService
{
    private KnowledgeRepository $repo;

    public function __construct()
    {
        $this->repo = new KnowledgeRepository();
    }

    public function home(): array
    {
        return [
            'categories' => $this->repo->categories(),
            'featured' => $this->repo->articles(['status' => 'published'], 6),
            'faqs' => $this->repo->articles(['status' => 'published', 'is_faq' => 1], 8),
            'recent' => $this->repo->articles(['status' => 'published'], 8),
        ];
    }

    public function article(string $slug): ?array
    {
        $article = $this->repo->findBySlug($slug);
        if (!$article) {
            return null;
        }

        $this->repo->incrementViews((int)$article->id, (int)$article->views);
        $article->views++;

        return [
            'article' => $article,
            'attachments' => $this->repo->attachments((int)$article->id),
            'related' => $this->repo->articles([
                'status' => 'published',
                'category_id' => $article->category_id,
            ], 5),
        ];
    }

    public function articleForEdit(string $slug): ?array
    {
        $article = $this->repo->findBySlug($slug);
        if (!$article) {
            return null;
        }

        return [
            'article' => $article,
            'attachments' => $this->repo->attachments((int)$article->id),
        ];
    }

    public function formData(): array
    {
        return ['categories' => $this->repo->categories()];
    }

    public function createArticle(array $input, int $authorId, array $files = []): array
    {
        $validation = $this->validateArticle($input);
        if (!$validation['success']) {
            return $validation;
        }

        $title = trim($input['title']);
        $slug = $this->uniqueSlug($title);
        $id = $this->repo->createArticle($this->articlePayload($input, $authorId, $slug));
        $this->storeAttachments($id, $authorId, $files);

        return ['success' => true, 'id' => $id, 'slug' => $slug, 'message' => 'Knowledge article saved.'];
    }

    public function updateArticle(string $slug, array $input, int $authorId, array $files = []): array
    {
        $article = $this->repo->findRawBySlug($slug);
        if (!$article) {
            return ['success' => false, 'message' => 'Article not found.'];
        }

        $validation = $this->validateArticle($input);
        if (!$validation['success']) {
            return $validation;
        }

        $newSlug = $this->uniqueSlug(trim($input['title']), (int)$article->id);
        $payload = $this->articlePayload($input, $authorId, $newSlug);
        unset($payload['author_id']);
        $this->repo->updateArticle((int)$article->id, $payload);
        $this->storeAttachments((int)$article->id, $authorId, $files);

        return ['success' => true, 'slug' => $newSlug, 'message' => 'Knowledge article updated.'];
    }

    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $boolean = $this->booleanQuery($query);
        try {
            $results = $this->repo->searchFullText($boolean);
            if (!empty($results)) {
                return $results;
            }
        } catch (\Exception $e) {
            // Some older MySQL setups reject FULLTEXT on short terms; LIKE fallback keeps search usable.
        }

        return $this->repo->searchLike($query);
    }

    public function categories(): array
    {
        return $this->repo->categories();
    }

    public function createCategory(array $input): array
    {
        if (trim($input['name'] ?? '') === '') {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        try {
            $id = $this->repo->createCategory($this->categoryPayload($input));
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create category: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Knowledge category created.'];
    }

    public function updateCategory(int $id, array $input): array
    {
        if (trim($input['name'] ?? '') === '') {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        try {
            $this->repo->updateCategory($id, $this->categoryPayload($input));
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update category: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Knowledge category updated.'];
    }

    private function validateArticle(array $input): array
    {
        foreach (['title', 'content', 'category_id', 'status'] as $field) {
            if (trim((string)($input[$field] ?? '')) === '') {
                return ['success' => false, 'message' => ucwords(str_replace('_', ' ', $field)) . ' is required.'];
            }
        }
        return ['success' => true];
    }

    private function articlePayload(array $input, int $authorId, string $slug): array
    {
        $content = trim($input['content']);
        return [
            'category_id' => (int)$input['category_id'],
            'title' => trim($input['title']),
            'slug' => $slug,
            'content' => $content,
            'excerpt' => trim($input['excerpt'] ?? '') ?: substr(strip_tags($content), 0, 180),
            'author_id' => $authorId,
            'status' => in_array($input['status'] ?? 'draft', ['draft', 'published', 'archived'], true) ? $input['status'] : 'draft',
            'article_type' => in_array($input['article_type'] ?? 'guide', ['guide', 'faq', 'procedure', 'policy', 'troubleshooting'], true) ? $input['article_type'] : 'guide',
            'is_faq' => isset($input['is_faq']) || ($input['article_type'] ?? '') === 'faq' ? 1 : 0,
            'is_featured' => isset($input['is_featured']) ? 1 : 0,
            'tags' => trim($input['tags'] ?? ''),
        ];
    }

    private function categoryPayload(array $input): array
    {
        return [
            'name' => trim($input['name']),
            'slug' => $this->slug(trim($input['slug'] ?? '') ?: $input['name']),
            'description' => trim($input['description'] ?? ''),
            'icon' => trim($input['icon'] ?? 'bi-journal-bookmark'),
            'color' => trim($input['color'] ?? '#1a56db'),
            'sort_order' => (int)($input['sort_order'] ?? 0),
        ];
    }

    private function uniqueSlug(string $title, int $excludeId = 0): string
    {
        $base = $this->slug($title);
        $slug = $base;
        $counter = 2;
        while ($this->repo->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }
        return $slug;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value)));
        return trim($slug, '-') ?: 'article';
    }

    private function booleanQuery(string $query): string
    {
        $terms = preg_split('/\s+/', $query);
        $terms = array_filter(array_map(fn(string $term) => preg_replace('/[^A-Za-z0-9]/', '', $term), $terms));
        if (empty($terms)) {
            return $query;
        }
        return implode(' ', array_map(fn(string $term) => '+' . $term . '*', $terms));
    }

    private function storeAttachments(int $articleId, int $userId, array $files): void
    {
        if (empty($files['attachments']['name']) || !is_array($files['attachments']['name'])) {
            return;
        }

        $uploadDir = PUBLIC_PATH . '/uploads/knowledge';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files['attachments']['name'] as $idx => $name) {
            if (($files['attachments']['error'][$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $stored = uniqid('kb_', true) . ($ext ? '.' . $ext : '');
            $target = $uploadDir . '/' . $stored;
            if (!move_uploaded_file($files['attachments']['tmp_name'][$idx], $target)) {
                continue;
            }

            $this->repo->addAttachment([
                'article_id' => $articleId,
                'uploaded_by' => $userId,
                'original_name' => $name,
                'stored_name' => $stored,
                'file_path' => 'knowledge/' . $stored,
                'mime_type' => $files['attachments']['type'][$idx] ?? '',
                'file_size' => (int)($files['attachments']['size'][$idx] ?? 0),
            ]);
        }
    }
}
