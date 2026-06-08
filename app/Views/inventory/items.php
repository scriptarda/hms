<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Inventory List</h1>
        <p>Track spare parts, consumables, stock levels, reorder thresholds, locations, and suppliers.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a href="<?= View::url('inventory/reorder-alerts') ?>" class="btn btn-outline-danger"><i class="bi bi-exclamation-triangle me-1"></i>Alerts</a>
        <a href="<?= View::url('inventory/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= View::url('inventory/items') ?>" class="row g-2">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?><option value="<?= (int)$cat->id ?>" <?= (string)($filters['category_id'] ?? '') === (string)$cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="supplier_id">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier->id ?>" <?= (string)($filters['supplier_id'] ?? '') === (string)$supplier->id ? 'selected' : '' ?>><?= htmlspecialchars($supplier->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="stock">
                    <option value="">All Stock</option>
                    <option value="ok" <?= ($filters['stock'] ?? '') === 'ok' ? 'selected' : '' ?>>Healthy</option>
                    <option value="low" <?= ($filters['stock'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="out" <?= ($filters['stock'] ?? '') === 'out' ? 'selected' : '' ?>>Out</option>
                </select>
            </div>
            <div class="col-md-3"><input class="form-control form-control-sm" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search SKU, item, location"></div>
            <div class="col-md-1 d-grid"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="inventoryTable">
                <thead><tr><th>SKU</th><th>Item</th><th>Category</th><th>Location</th><th>Stock</th><th>Reorder</th><th>Supplier</th><th>Value</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No inventory items found.</td></tr>
                <?php else: foreach ($items as $item): ?>
                    <tr class="<?= $item->stock_state === 'out' ? 'table-danger' : ($item->stock_state === 'low' ? 'table-warning-subtle' : '') ?>">
                        <td><a href="<?= View::url('inventory/' . $item->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($item->sku) ?></a></td>
                        <td><?= htmlspecialchars($item->name) ?> <?php if ($item->stock_state !== 'ok'): ?><span class="badge <?= $item->stock_state === 'out' ? 'bg-danger' : 'bg-warning text-dark' ?> ms-1"><?= strtoupper($item->stock_state) ?></span><?php endif; ?></td>
                        <td><small><?= htmlspecialchars($item->category_name ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($item->location ?? '-') ?></small></td>
                        <td><span class="fw-bold"><?= (int)$item->quantity ?></span> <small class="text-muted"><?= htmlspecialchars($item->unit) ?></small></td>
                        <td><small><?= (int)$item->reorder_level ?> / order <?= (int)$item->reorder_quantity ?></small></td>
                        <td><small><?= htmlspecialchars($item->display_supplier ?: '-') ?></small></td>
                        <td><small class="fw-semibold">$<?= number_format(((float)$item->unit_cost * (int)$item->quantity), 2) ?></small></td>
                        <td class="text-end"><a href="<?= View::url('inventory/' . $item->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>$(function(){ $('#inventoryTable').DataTable({ searching:false, pageLength:25 }); });</script>
<?php View::endSection(); ?>
