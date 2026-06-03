<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Article: <?= htmlspecialchars($article->title) ?></h1>
    <p>Update documentation details, category structure, tags, or publishing state.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('knowledge/' . $article->slug . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Article Title *</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($article->title) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Category *</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $article->category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Excerpt / Brief Summary</label>
                    <input type="text" class="form-control" name="excerpt" value="<?= htmlspecialchars($article->excerpt) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Article Content *</label>
                    <textarea class="form-control" name="content" rows="12" required><?= htmlspecialchars($article->content) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Search Tags</label>
                    <input type="text" class="form-control" name="tags" value="<?= htmlspecialchars($article->tags) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="draft" <?= $article->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $article->status === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?= $article->is_featured ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isFeatured">
                            Pin as Featured / Popular
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('knowledge/' . $article->slug) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
