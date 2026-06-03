<?php use App\Helpers\View; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canPublish = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician']);
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Knowledge Base</h1>
        <p>Search standard operating procedures, clinical hardware calibration manuals, and systems guides.</p>
    </div>
    <?php if ($canPublish): ?>
        <a href="<?= View::url('knowledge/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus me-1"></i>Publish Article</a>
    <?php endif; ?>
</div>

<!-- Search Bar Area -->
<div class="card bg-primary text-white shadow-sm border-0 mb-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;">
    <div class="card-body p-5 text-center position-relative" style="z-index: 2;">
        <h3 class="fw-bold mb-3">How can we help you today?</h3>
        <div class="position-relative mx-auto" style="max-width: 600px;">
            <div class="input-group input-group-lg shadow">
                <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0" id="kbSearchInput" placeholder="Search keywords, calibration errors, EMR setup..." autocomplete="off">
            </div>
            <!-- Live Search Results Dropdown -->
            <div class="position-absolute w-100 bg-white text-dark rounded shadow-lg text-start mt-2 border d-none overflow-hidden" id="kbSearchResults" style="z-index: 100; max-height: 350px; overflow-y: auto;">
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Categories Grid -->
    <div class="col-lg-8">
        <h4 class="fw-bold mb-3">Knowledge Categories</h4>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 border-start border-4" style="border-start-color: <?= $cat->color ?: '#1a56db' ?> !important;">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded p-2 text-white" style="background-color: <?= $cat->color ?: '#1a56db' ?>;">
                                    <i class="bi <?= $cat->icon ?: 'bi-journal-bookmark' ?> fs-4"></i>
                                </div>
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($cat->name) ?></h5>
                            </div>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($cat->description ?? '') ?></p>
                            <span class="badge bg-secondary-subtle text-secondary-dark small"><?= $categoryCounts[$cat->id] ?? 0 ?> Articles</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Column: Featured Articles -->
    <div class="col-lg-4">
        <h4 class="fw-bold mb-3">Popular & Featured Articles</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($featured)): ?>
                        <p class="text-muted p-4 text-center small mb-0">No published articles yet.</p>
                    <?php else: foreach ($featured as $art): ?>
                        <a href="<?= View::url('knowledge/' . $art->slug) ?>" class="list-group-item list-group-item-action p-3 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge" style="background-color: <?= $art->category_color ?>;"><?= htmlspecialchars($art->category_name) ?></span>
                                <small class="text-muted"><i class="bi bi-eye me-1"></i><?= $art->views ?></small>
                            </div>
                            <h6 class="fw-bold text-primary-dark mb-1"><?= htmlspecialchars($art->title) ?></h6>
                            <p class="text-muted small mb-0"><?= htmlspecialchars(substr($art->excerpt, 0, 80)) ?>...</p>
                            <small class="text-muted d-block mt-2" style="font-size:0.75rem;">By: <?= htmlspecialchars($art->author_name) ?></small>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        const $input = $('#kbSearchInput');
        const $results = $('#kbSearchResults');

        $input.on('keyup', function() {
            const query = $(this).val().trim();
            if (query.length < 2) {
                $results.addClass('d-none').html('');
                return;
            }

            $.get('<?= View::url("knowledge/search") ?>', { query: query }, function(data) {
                $results.html('');
                if (data.length === 0) {
                    $results.append('<div class="p-3 text-muted small text-center">No articles found matching search query.</div>');
                } else {
                    data.forEach(item => {
                        $results.append(`
                            <a href="<?= View::url("knowledge") ?>/${item.slug}" class="d-block p-3 border-bottom text-decoration-none list-group-item-action">
                                <div class="fw-bold small text-primary-dark mb-1">${item.title}</div>
                                <small class="text-muted d-block text-truncate" style="max-width: 90%;">${item.excerpt}</small>
                                <span class="badge bg-secondary-subtle text-secondary-dark mt-1" style="font-size: 0.65rem;">${item.category_name}</span>
                            </a>
                        `);
                    });
                }
                $results.removeClass('d-none');
            }, 'json');
        });

        // Hide results on clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#kbSearchInput, #kbSearchResults').length) {
                $results.addClass('d-none');
            }
        });
    });
</script>
<?php View::endSection(); ?>
