<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Reorder Alerts</h1><p>Items at or below reorder threshold, sorted by urgency.</p></div>
    <div class="page-actions"><a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary">Dashboard</a><a href="<?= View::url('inventory/purchase-requests') ?>" class="btn btn-outline-primary">Purchase Requests</a></div>
</div>
<div class="row g-3">
<?php if (empty($items)): ?>
    <div class="col-12"><div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">No reorder alerts right now.</div></div></div>
<?php else: foreach ($items as $item): ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card shadow-sm border-0 h-100 <?= $item->quantity <= 0 ? 'border-start border-danger border-3' : 'border-start border-warning border-3' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <a href="<?= View::url('inventory/' . $item->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($item->sku) ?></a>
                    <span class="badge <?= $item->quantity <= 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= $item->quantity <= 0 ? 'OUT' : 'LOW' ?></span>
                </div>
                <h6 class="fw-bold"><?= htmlspecialchars($item->name) ?></h6>
                <div class="small text-muted mb-3">
                    <div>Current: <strong><?= (int)$item->quantity ?></strong> <?= htmlspecialchars($item->unit) ?></div>
                    <div>Reorder level: <?= (int)$item->reorder_level ?> <?= htmlspecialchars($item->unit) ?></div>
                    <div>Supplier: <?= htmlspecialchars($item->display_supplier ?: '-') ?></div>
                </div>
                <form action="<?= View::url('inventory/purchase-requests/store') ?>" method="POST" class="row g-2">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="item_id" value="<?= (int)$item->id ?>">
                    <input type="hidden" name="supplier_id" value="<?= (int)($item->supplier_id ?? 0) ?>">
                    <div class="col-7"><input type="number" class="form-control form-control-sm" name="quantity" value="<?= (int)($item->reorder_quantity ?: max($item->reorder_level - $item->quantity, 1)) ?>" min="1"></div>
                    <div class="col-5 d-grid"><button class="btn btn-sm btn-primary">Request</button></div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
<?php View::endSection(); ?>
