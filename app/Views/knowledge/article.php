<?php use App\Helpers\View; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canEdit = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician'], true);
View::startSection('content'); ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= View::url('knowledge') ?>">Knowledge Base</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars(substr($article->title, 0, 45)) ?></li>
    </ol>
</nav>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap border-bottom pb-4 mb-4">
                    <div>
                        <span class="badge mb-2" style="background:<?= htmlspecialchars($article->category_color ?: '#1a56db') ?>"><?= htmlspecialchars($article->category_name ?? 'General') ?></span>
                        <?php if ($article->is_faq): ?><span class="badge bg-info mb-2">FAQ</span><?php endif; ?>
                        <h1 class="fw-bold mb-2"><?= htmlspecialchars($article->title) ?></h1>
                        <div class="d-flex gap-3 text-muted small flex-wrap">
                            <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($article->author_name) ?></span>
                            <span><i class="bi bi-calendar-event me-1"></i><?= View::date($article->updated_at) ?></span>
                            <span><i class="bi bi-eye me-1"></i><?= (int)$article->views ?> views</span>
                            <span><i class="bi bi-file-text me-1"></i><?= htmlspecialchars(ucwords(str_replace('_', ' ', $article->article_type))) ?></span>
                        </div>
                    </div>
                    <?php if ($canEdit): ?><a href="<?= View::url('knowledge/' . $article->slug . '/edit') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
                </div>
                <div class="kb-article-body" style="font-size:1.02rem;line-height:1.8;">
                    <?= $article->content ?>
                </div>
                <?php if (!empty($article->tags)): ?>
                    <div class="border-top pt-4 mt-5">
                        <?php foreach (array_filter(array_map('trim', explode(',', $article->tags))) as $tag): ?>
                            <span class="badge bg-light text-dark border me-1">#<?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">Attachments</div>
            <div class="card-body">
                <?php if (empty($attachments)): ?>
                    <p class="text-muted small mb-0">No attachments.</p>
                <?php else: foreach ($attachments as $file): ?>
                    <a href="<?= View::url('uploads/' . $file->file_path) ?>" target="_blank" class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 text-decoration-none">
                        <span><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($file->original_name) ?></span>
                        <small class="text-muted"><?= number_format(((int)$file->file_size) / 1024, 1) ?> KB</small>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header">Related Articles</div>
            <div class="list-group list-group-flush">
                <?php foreach ($related as $item): if ((int)$item->id === (int)$article->id) continue; ?>
                    <a href="<?= View::url('knowledge/' . $item->slug) ?>" class="list-group-item list-group-item-action">
                        <div class="fw-semibold"><?= htmlspecialchars($item->title) ?></div>
                        <small class="text-muted"><?= htmlspecialchars(substr($item->excerpt ?? '', 0, 80)) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
