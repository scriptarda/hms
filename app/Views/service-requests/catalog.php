<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Service Catalog</h1>
        <p>Request hardware, software, identity, network, and clinical equipment through governed workflows.</p>
    </div>
    <a href="<?= View::url('service-requests') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-check me-1"></i>Track Requests</a>
</div>

<div class="row g-3">
    <?php foreach ($catalogItems as $item): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width:46px;height:46px;border-radius:8px;background:<?= htmlspecialchars($item->color) ?>;">
                            <i class="bi <?= htmlspecialchars($item->icon) ?> fs-4"></i>
                        </div>
                        <div>
                            <div class="text-uppercase text-muted fw-semibold small mb-1"><?= htmlspecialchars($item->category) ?></div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($item->name) ?></h5>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($item->short_description) ?></p>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap mt-auto pt-3">
                        <span class="badge bg-light text-dark border"><?= count($item->schema) ?> fields</span>
                        <span class="badge bg-light text-dark border"><?= ucwords(str_replace('_', ' ', $item->approval_mode)) ?> approval</span>
                        <span class="badge bg-light text-dark border"><?= (int)$item->sla_hours ?>h target</span>
                    </div>

                    <a href="<?= View::url('service-requests/create/' . $item->type) ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-right-circle me-1"></i>Start Request
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php View::endSection(); ?>
