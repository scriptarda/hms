<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Knowledge FAQ</h1><p>Frequently asked questions from published knowledge articles.</p></div>
    <div class="page-actions"><a href="<?= View::url('knowledge') ?>" class="btn btn-outline-secondary">Knowledge Home</a><a href="<?= View::url('knowledge/create') ?>" class="btn btn-primary">Create FAQ</a></div>
</div>
<div class="accordion" id="faqAccordion">
    <?php if (empty($faqs)): ?>
        <div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">No FAQ articles have been published.</div></div>
    <?php else: foreach ($faqs as $idx => $faq): ?>
        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= (int)$faq->id ?>">
                    <span class="fw-semibold"><?= htmlspecialchars($faq->title) ?></span>
                </button>
            </h2>
            <div id="faq<?= (int)$faq->id ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <p class="text-muted"><?= htmlspecialchars($faq->excerpt ?? '') ?></p>
                    <a href="<?= View::url('knowledge/' . $faq->slug) ?>" class="btn btn-sm btn-outline-primary">Open Article</a>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php View::endSection(); ?>
