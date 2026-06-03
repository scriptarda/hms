<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Publish Knowledge Article</h1>
    <p>Create guides, procedures, or FAQs to assist staff in solving issues.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('knowledge/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Article Title *</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g. Standard calibration procedure for Hamilton Ventilators" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Category *</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Excerpt / Brief Summary</label>
                    <input type="text" class="form-control" name="excerpt" placeholder="A single-sentence overview displayed in searches (optional)">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Article Content (HTML/Rich-text format allowed) *</label>
                    <textarea class="form-control" name="content" rows="12" placeholder="Write article content here. You can use standard HTML formatting tags like <h2>, <p>, <ul>, <li>, <strong>, etc." required></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Search Tags</label>
                    <input type="text" class="form-control" name="tags" placeholder="e.g. VENTILATOR, ICU, CALIBRATION">
                    <div class="form-text">Comma-separated tags for index matching.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="draft">Draft - Private</option>
                        <option value="published" selected>Published - Public</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured">
                        <label class="form-check-label fw-semibold" for="isFeatured">
                            Pin as Featured / Popular
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Publish Article</button>
                <a href="<?= View::url('knowledge') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
