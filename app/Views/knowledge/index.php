<?php use App\Helpers\View; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canPublish = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician'], true);
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Knowledge Base</h1><p>Search SOPs, FAQs, troubleshooting guides, policies, and equipment procedures.</p></div>
    <div class="page-actions">
        <a href="<?= View::url('knowledge/faq') ?>" class="btn btn-outline-primary"><i class="bi bi-question-circle me-1"></i>FAQ</a>
        <?php if ($canPublish): ?><a href="<?= View::url('knowledge/categories') ?>" class="btn btn-outline-secondary"><i class="bi bi-tags me-1"></i>Categories</a><a href="<?= View::url('knowledge/create') ?>" class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i>Create Article</a><?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="position-relative">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="kbSearchInput" placeholder="Search by error code, asset model, workflow, policy, or tag" autocomplete="off">
            </div>
            <div class="position-absolute w-100 bg-white rounded shadow border d-none mt-2 overflow-hidden" id="kbSearchResults" style="z-index:100;max-height:420px;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header">Categories</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($categories as $cat): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100" style="border-left:4px solid <?= htmlspecialchars($cat->color ?: '#1a56db') ?> !important;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded text-white d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;background:<?= htmlspecialchars($cat->color ?: '#1a56db') ?>"><i class="bi <?= htmlspecialchars($cat->icon ?: 'bi-journal-bookmark') ?>"></i></span>
                                    <div><div class="fw-bold"><?= htmlspecialchars($cat->name) ?></div><small class="text-muted"><?= (int)$cat->article_count ?> articles, <?= (int)$cat->faq_count ?> FAQs</small></div>
                                </div>
                                <p class="small text-muted mb-0"><?= htmlspecialchars($cat->description ?? '') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header">Featured Articles</div>
            <div class="list-group list-group-flush">
                <?php foreach ($featured as $article): ?>
                    <a href="<?= View::url('knowledge/' . $article->slug) ?>" class="list-group-item list-group-item-action">
                        <span class="badge mb-2" style="background:<?= htmlspecialchars($article->category_color ?: '#6c757d') ?>"><?= htmlspecialchars($article->category_name ?? 'General') ?></span>
                        <div class="fw-bold"><?= htmlspecialchars($article->title) ?></div>
                        <small class="text-muted"><?= htmlspecialchars(substr($article->excerpt ?? '', 0, 90)) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between"><span>Recent Articles</span><a href="<?= View::url('knowledge/search?query=') ?>" class="small">Search</a></div>
            <div class="card-body">
                <?php foreach ($recent as $article): ?>
                    <div class="border-bottom py-2">
                        <a href="<?= View::url('knowledge/' . $article->slug) ?>" class="fw-semibold"><?= htmlspecialchars($article->title) ?></a>
                        <div class="small text-muted"><?= htmlspecialchars($article->category_name ?? '-') ?> - <?= View::date($article->updated_at) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between"><span>FAQ Highlights</span><a href="<?= View::url('knowledge/faq') ?>" class="small">View FAQ</a></div>
            <div class="card-body">
                <?php foreach ($faqs as $article): ?>
                    <div class="border-bottom py-2">
                        <a href="<?= View::url('knowledge/' . $article->slug) ?>" class="fw-semibold"><?= htmlspecialchars($article->title) ?></a>
                        <div class="small text-muted"><?= htmlspecialchars(substr(strip_tags($article->excerpt ?? ''), 0, 100)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
$(function() {
    const $input = $('#kbSearchInput');
    const $results = $('#kbSearchResults');
    const esc = value => String(value || '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    let timer = null;
    $input.on('keyup', function() {
        clearTimeout(timer);
        const query = this.value.trim();
        if (query.length < 2) { $results.addClass('d-none').empty(); return; }
        timer = setTimeout(function() {
            $.get('<?= View::url("knowledge/api/search") ?>', { query }, function(data) {
                $results.empty();
                if (!data.length) {
                    $results.append('<div class="p-3 text-muted small text-center">No matching articles found.</div>');
                }
                data.forEach(function(item) {
                    $results.append(`<a href="<?= View::url("knowledge") ?>/${item.slug}" class="d-block p-3 border-bottom text-decoration-none">
                        <div class="d-flex justify-content-between"><strong>${esc(item.title)}</strong><span class="badge bg-light text-dark">${esc(item.article_type)}</span></div>
                        <small class="text-muted d-block">${esc(item.excerpt)}</small>
                        <small class="text-muted">${esc(item.category_name || 'General')} &middot; ${Number(item.views || 0)} views</small>
                    </a>`);
                });
                $results.removeClass('d-none');
            }, 'json');
        }, 180);
    });
    $(document).on('click', function(e) { if (!$(e.target).closest('#kbSearchInput,#kbSearchResults').length) $results.addClass('d-none'); });
});
</script>
<?php View::endSection(); ?>
