<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Inventory & Stock Valuation</h1>
        <p>Analyze reorder triggers, material assets, and total inventory value distributions.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports/export/inventory?format=csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        <a href="<?= View::url('reports/export/inventory?format=excel') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports Center</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Stock Valuation List -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Top 10 Valued Stock Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Item Name / SKU</th>
                                <th class="text-end pe-4">Total Stock Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topValuation)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted small">No inventory valuation records found.</td>
                                </tr>
                            <?php else: foreach ($topValuation as $tv): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($tv->name) ?> <small class="text-muted text-monospace">(<?= htmlspecialchars($tv->sku) ?>)</small></td>
                                    <td class="text-end pe-4 fw-bold text-success">$<?= number_format($tv->total_val ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0 border-start border-warning border-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold text-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock Items Requiring Reorder</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">SKU Code</th>
                                <th>Item Name</th>
                                <th class="text-end pe-4">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStock)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>All stock levels are currently safe.</td>
                                </tr>
                            <?php else: foreach ($lowStock as $ls): ?>
                                <tr>
                                    <td class="ps-4"><a href="<?= View::url('inventory/' . $ls->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($ls->sku) ?></a></td>
                                    <td><?= htmlspecialchars($ls->name) ?></td>
                                    <td class="text-end pe-4 fw-bold text-danger"><?= $ls->quantity ?> <?= htmlspecialchars($ls->unit) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
