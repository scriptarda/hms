<?php use App\Helpers\View; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canEdit = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician']);
View::startSection('content'); ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= View::url('knowledge') ?>">Knowledge Base</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(substr($article->title, 0, 30)) ?>...</li>
    </ol>
</nav>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 p-md-5">
        <!-- Title and actions -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3">
            <div>
                <span class="badge mb-2 text-white" style="background-color: <?= $article->category_color ?: '#1a56db' ?>;"><?= htmlspecialchars($article->category_name) ?></span>
                <h1 class="fw-extrabold text-primary-dark mb-2" style="font-size: 2.2rem; font-weight: 800;"><?= htmlspecialchars($article->title) ?></h1>
                
                <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                    <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($article->author_name) ?></span>
                    <span><i class="bi bi-calendar-event me-1"></i><?= View::date($article->created_at) ?></span>
                    <span><i class="bi bi-eye me-1"></i><?= $article->views ?> Views</span>
                </div>
            </div>
            
            <?php if ($canEdit): ?>
                <a href="<?= View::url('knowledge/' . $article->slug . '/edit') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Article</a>
            <?php endif; ?>
        </div>

        <!-- Article Content -->
        <div class="kb-article-body text-primary-dark" style="font-size: 1.05rem; line-height: 1.8;">
            <!-- Render Raw Content safely allowing formatted HTML structure -->
            <?= $article->content ?>
        </div>

        <!-- Tags -->
        <?php if (!empty($article->tags)): ?>
            <div class="border-top pt-4 mt-5">
                <span class="text-muted small fw-bold d-inline-block me-2">TAGS:</span>
                <?php 
                $tags = explode(',', $article->tags);
                foreach ($tags as $tag): 
                    $t = trim($tag);
                    if (empty($t)) continue;
                ?>
                    <span class="badge bg-light text-primary-dark border me-1">#<?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
