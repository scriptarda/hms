<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Inventory Dashboard</h1>
        <p>Monitor spare parts, stock risk, purchase requests, and supplier coverage.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('inventory/items') ?>" class="btn btn-outline-primary"><i class="bi bi-box-seam me-1"></i>Inventory List</a>
        <a href="<?= View::url('inventory/purchase-requests') ?>" class="btn btn-outline-secondary"><i class="bi bi-receipt me-1"></i>Purchase Requests</a>
        <a href="<?= View::url('inventory/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">SKUs</span><div class="kpi-icon blue"><i class="bi bi-box"></i></div></div><div class="kpi-value"><?= (int)$metrics['total_items'] ?></div></div></div>
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Low</span><div class="kpi-icon yellow"><i class="bi bi-exclamation-triangle"></i></div></div><div class="kpi-value text-warning"><?= (int)$metrics['low_stock'] ?></div></div></div>
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Out</span><div class="kpi-icon red"><i class="bi bi-x-octagon"></i></div></div><div class="kpi-value text-danger"><?= (int)$metrics['out_of_stock'] ?></div></div></div>
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Open PRs</span><div class="kpi-icon green"><i class="bi bi-receipt"></i></div></div><div class="kpi-value"><?= (int)$metrics['open_requests'] ?></div></div></div>
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Suppliers</span><div class="kpi-icon blue"><i class="bi bi-truck"></i></div></div><div class="kpi-value"><?= (int)$metrics['suppliers'] ?></div></div></div>
    <div class="col-md-2"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Value</span><div class="kpi-icon green"><i class="bi bi-cash-stack"></i></div></div><div class="kpi-value" style="font-size:1.4rem">$<?= number_format((float)$metrics['inventory_value'], 0) ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Reorder Alerts</span>
                <a href="<?= View::url('inventory/reorder-alerts') ?>" class="btn btn-sm btn-outline-danger">Review All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-4">SKU</th><th>Item</th><th>Stock</th><th>Supplier</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($alerts)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No items are below reorder level.</td></tr>
                        <?php else: foreach ($alerts as $item): ?>
                            <tr>
                                <td class="ps-4"><a href="<?= View::url('inventory/' . $item->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($item->sku) ?></a></td>
                                <td><?= htmlspecialchars($item->name) ?></td>
                                <td><span class="fw-bold <?= $item->quantity <= 0 ? 'text-danger' : 'text-warning' ?>"><?= (int)$item->quantity ?></span> / <small><?= (int)$item->reorder_level ?> <?= htmlspecialchars($item->unit) ?></small></td>
                                <td><small><?= htmlspecialchars($item->display_supplier ?: '-') ?></small></td>
                                <td><a href="<?= View::url('inventory/purchase-requests?item_id=' . $item->id) ?>" class="btn btn-sm btn-outline-primary">Request</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header">Recent Stock Movement</div>
            <div class="card-body">
                <?php if (empty($recentTransactions)): ?>
                    <p class="text-muted small text-center mb-0">No stock movement recorded.</p>
                <?php else: foreach ($recentTransactions as $tx): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <a href="<?= View::url('inventory/' . $tx->item_id) ?>" class="fw-semibold"><?= htmlspecialchars($tx->sku) ?></a>
                            <small class="d-block text-muted"><?= htmlspecialchars($tx->item_name) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= in_array($tx->type, ['in','return'], true) ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars(strtoupper($tx->type)) ?></span>
                            <small class="d-block text-muted"><?= (int)$tx->quantity ?> <?= htmlspecialchars($tx->unit) ?></small>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
