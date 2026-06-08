<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\KnowledgeService;

class KnowledgeController extends BaseController
{
    private KnowledgeService $service;

    public function __construct()
    {
        $this->service = new KnowledgeService();
    }

    public function index(): void
    {
        $this->view('knowledge/index', ['pageTitle' => 'Knowledge Base'] + $this->service->home());
    }

    public function create(): void
    {
        $this->view('knowledge/create', ['pageTitle' => 'Create Article'] + $this->service->formData());
    }

    public function store(): void
    {
        $result = $this->service->createArticle($_POST, (int)Session::userId(), $_FILES);
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/knowledge/create');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/knowledge/' . $result['slug']);
    }

    public function article(string $slug): void
    {
        $bundle = $this->service->article($slug);
        if (!$bundle) {
            $this->abort(404);
        }

        $bundle['pageTitle'] = $bundle['article']->title;
        $this->view('knowledge/article', $bundle);
    }

    public function edit(string $slug): void
    {
        $bundle = $this->service->articleForEdit($slug);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('knowledge/edit', [
            'pageTitle' => 'Edit Article: ' . $bundle['article']->title,
            'article' => $bundle['article'],
            'attachments' => $bundle['attachments'],
        ] + $this->service->formData());
    }

    public function update(string $slug): void
    {
        $result = $this->service->updateArticle($slug, $_POST, (int)Session::userId(), $_FILES);
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/knowledge/' . $slug . '/edit');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/knowledge/' . $result['slug']);
    }

    public function categories(): void
    {
        $this->view('knowledge/categories', [
            'pageTitle' => 'Knowledge Categories',
            'categories' => $this->service->categories(),
        ]);
    }

    public function storeCategory(): void
    {
        $result = $this->service->createCategory($_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/knowledge/categories');
    }

    public function updateCategory(string $id): void
    {
        $result = $this->service->updateCategory((int)$id, $_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/knowledge/categories');
    }

    public function faq(): void
    {
        $home = $this->service->home();
        $this->view('knowledge/faq', [
            'pageTitle' => 'Knowledge FAQ',
            'faqs' => $home['faqs'],
            'categories' => $home['categories'],
        ]);
    }

    public function search(): void
    {
        $this->apiSearch();
    }

    public function apiSearch(): void
    {
        $query = trim($_GET['query'] ?? $_GET['q'] ?? '');
        $results = array_map(fn(object $article) => [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'category_name' => $article->category_name,
            'article_type' => $article->article_type,
            'is_faq' => (int)$article->is_faq,
            'views' => (int)$article->views,
        ], $this->service->search($query));

        $this->json($results);
    }

    public function apiCategories(): void
    {
        $this->json(['data' => $this->service->categories()]);
    }
}
