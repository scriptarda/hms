<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class KnowledgeController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $categories = $this->db->fetchAll("SELECT * FROM knowledge_categories WHERE deleted_at IS NULL ORDER BY sort_order ASC, name ASC");
        
        // Fetch featured or recent articles
        $featured = $this->db->fetchAll(
            "SELECT a.*, c.name as category_name, c.color as category_color,
                    CONCAT(u.first_name, ' ', u.last_name) as author_name
             FROM knowledge_articles a
             LEFT JOIN knowledge_categories c ON a.category_id = c.id
             JOIN users u ON a.author_id = u.id
             WHERE a.status = 'published' AND a.deleted_at IS NULL
             ORDER BY a.is_featured DESC, a.views DESC, a.created_at DESC LIMIT 5"
        );

        // Fetch category counts
        $counts = $this->db->fetchAll(
            "SELECT category_id, COUNT(*) as cnt 
             FROM knowledge_articles 
             WHERE status = 'published' AND deleted_at IS NULL 
             GROUP BY category_id"
        );
        $catCounts = [];
        foreach ($counts as $c) {
            $catCounts[$c->category_id] = (int)$c->cnt;
        }

        $this->view('knowledge/index', [
            'pageTitle' => 'Knowledge Base',
            'categories' => $categories,
            'featured' => $featured,
            'categoryCounts' => $catCounts
        ]);
    }

    public function create(): void
    {
        $categories = $this->db->fetchAll("SELECT * FROM knowledge_categories WHERE deleted_at IS NULL ORDER BY name");
        $this->view('knowledge/create', [
            'pageTitle' => 'Publish Article',
            'categories' => $categories
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('title')->required('content')->required('category_id')->required('status');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/knowledge/create');
        }

        $title = trim($_POST['title']);
        $slug = $this->generateSlug($title);
        
        $data = [
            'category_id' => (int)$_POST['category_id'],
            'title' => $title,
            'slug' => $slug,
            'content' => trim($_POST['content']),
            'excerpt' => trim($_POST['excerpt'] ?? substr(strip_tags($_POST['content']), 0, 150)),
            'author_id' => Session::userId(),
            'status' => $_POST['status'] ?? 'draft',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'tags' => trim($_POST['tags'] ?? ''),
        ];

        $id = $this->db->insert('knowledge_articles', $data);

        Session::flash('success', 'Knowledge article published.');
        $this->redirect('/knowledge/' . $slug);
    }

    public function article(string $slug): void
    {
        $article = $this->db->fetch(
            "SELECT a.*, c.name as category_name, c.color as category_color, c.slug as category_slug,
                    CONCAT(u.first_name, ' ', u.last_name) as author_name
             FROM knowledge_articles a
             LEFT JOIN knowledge_categories c ON a.category_id = c.id
             JOIN users u ON a.author_id = u.id
             WHERE a.slug = ? AND a.deleted_at IS NULL",
            [$slug]
        );

        if (!$article) $this->abort(404);

        // Increment view count
        $this->db->update('knowledge_articles', [
            'views' => $article->views + 1
        ], 'id = ?', [$article->id]);

        $this->view('knowledge/article', [
            'pageTitle' => $article->title,
            'article' => $article
        ]);
    }

    public function edit(string $slug): void
    {
        $article = $this->db->fetch("SELECT * FROM knowledge_articles WHERE slug = ? AND deleted_at IS NULL", [$slug]);
        if (!$article) $this->abort(404);

        $categories = $this->db->fetchAll("SELECT * FROM knowledge_categories WHERE deleted_at IS NULL ORDER BY name");
        $this->view('knowledge/edit', [
            'pageTitle' => 'Edit Article: ' . $article->title,
            'article' => $article,
            'categories' => $categories
        ]);
    }

    public function update(string $slug): void
    {
        $article = $this->db->fetch("SELECT * FROM knowledge_articles WHERE slug = ? AND deleted_at IS NULL", [$slug]);
        if (!$article) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('title')->required('content')->required('category_id')->required('status');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/knowledge/' . $slug . '/edit');
        }

        $title = trim($_POST['title']);
        $newSlug = $this->generateSlug($title, $article->id);

        $data = [
            'category_id' => (int)$_POST['category_id'],
            'title' => $title,
            'slug' => $newSlug,
            'content' => trim($_POST['content']),
            'excerpt' => trim($_POST['excerpt'] ?? substr(strip_tags($_POST['content']), 0, 150)),
            'status' => $_POST['status'] ?? 'draft',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'tags' => trim($_POST['tags'] ?? ''),
        ];

        $this->db->update('knowledge_articles', $data, 'id = ?', [$article->id]);

        Session::flash('success', 'Knowledge article updated.');
        $this->redirect('/knowledge/' . $newSlug);
    }

    public function search(): void
    {
        $query = trim($_GET['query'] ?? '');
        if (empty($query)) {
            $this->json([]);
            return;
        }

        // Robust database search utilizing both title and content
        $sql = "SELECT a.title, a.slug, a.excerpt, c.name as category_name
                FROM knowledge_articles a
                LEFT JOIN knowledge_categories c ON a.category_id = c.id
                WHERE a.status = 'published' AND a.deleted_at IS NULL
                AND (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)
                ORDER BY a.views DESC LIMIT 10";
        
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        $results = $this->db->fetchAll($sql, $params);

        $this->json($results);
    }

    private function generateSlug(string $title, int $excludeId = 0): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = rtrim($slug, '-');

        // Check if slug exists
        $sql = "SELECT id FROM knowledge_articles WHERE slug = ? AND deleted_at IS NULL";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $exists = $this->db->fetch($sql, $params);
        if ($exists) {
            $slug .= '-' . rand(100, 999);
        }

        return $slug;
    }
}
