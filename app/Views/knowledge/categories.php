<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Category Management</h1><p>Organize articles, FAQ content, SOPs, and troubleshooting guides.</p></div>
    <div class="page-actions"><a href="<?= View::url('knowledge') ?>" class="btn btn-outline-secondary">Knowledge Home</a><a href="<?= View::url('knowledge/create') ?>" class="btn btn-primary">Create Article</a></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header">New Category</div>
            <div class="card-body">
                <form action="<?= View::url('knowledge/categories/store') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" placeholder="auto-generated if blank"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label">Icon</label><input class="form-control" name="icon" value="bi-journal-bookmark"></div>
                        <div class="col-6 mb-3"><label class="form-label">Color</label><input type="color" class="form-control form-control-color" name="color" value="#1a56db"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Sort Order</label><input type="number" class="form-control" name="sort_order" value="0"></div>
                    <button class="btn btn-primary w-100">Save Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php foreach ($categories as $cat): ?>
                    <form action="<?= View::url('knowledge/categories/' . $cat->id . '/update') ?>" method="POST" class="border rounded p-3 mb-3">
                        <?= CSRF::field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3"><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars($cat->name) ?>"></div>
                            <div class="col-md-3"><label class="form-label small">Slug</label><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars($cat->slug) ?>"></div>
                            <div class="col-md-2"><label class="form-label small">Icon</label><input class="form-control form-control-sm" name="icon" value="<?= htmlspecialchars($cat->icon) ?>"></div>
                            <div class="col-md-1"><label class="form-label small">Color</label><input type="color" class="form-control form-control-color form-control-sm" name="color" value="<?= htmlspecialchars($cat->color ?: '#1a56db') ?>"></div>
                            <div class="col-md-1"><label class="form-label small">Sort</label><input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)$cat->sort_order ?>"></div>
                            <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                            <div class="col-12"><textarea class="form-control form-control-sm" name="description" rows="2"><?= htmlspecialchars($cat->description ?? '') ?></textarea></div>
                            <div class="col-12 small text-muted"><?= (int)$cat->article_count ?> articles, <?= (int)$cat->faq_count ?> FAQs</div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
