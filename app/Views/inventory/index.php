<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Inventory Control</h1>
        <p>Manage spare parts, repair materials, clinical consumables, and hardware stock limits.</p>
    </div>
    <a href="<?= View::url('inventory/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Inventory Item</a>
</div>

<!-- Widgets -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Total Stock Items</span><div class="kpi-icon blue"><i class="bi bi-box-seam"></i></div></div>
            <div class="kpi-value"><?= $metrics['total'] ?></div>
            <div class="kpi-change stable">— Active SKU registry</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Low Stock Alerts</span><div class="kpi-icon yellow"><i class="bi bi-exclamation-triangle"></i></div></div>
            <div class="kpi-value <?= $metrics['low'] > 0 ? 'text-warning' : '' ?>"><?= $metrics['low'] ?></div>
            <div class="kpi-change <?= $metrics['low'] > 0 ? 'down' : 'stable' ?>">
                <i class="bi <?= $metrics['low'] > 0 ? 'bi-arrow-down-right' : 'bi-dash' ?>"></i> <?= $metrics['low'] > 0 ? 'Needs immediate reorder' : 'All safe' ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Out of Stock</span><div class="kpi-icon red"><i class="bi bi-shield-x"></i></div></div>
            <div class="kpi-value <?= $metrics['out'] > 0 ? 'text-danger' : '' ?>"><?= $metrics['out'] ?></div>
            <div class="kpi-change <?= $metrics['out'] > 0 ? 'down' : 'stable' ?>">
                <i class="bi <?= $metrics['out'] > 0 ? 'bi-arrow-down-right' : 'bi-dash' ?>"></i> <?= $metrics['out'] > 0 ? 'Critical blockages' : 'All safe' ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="<?= View::url('inventory') ?>" class="row g-2 mb-4">
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" placeholder="Search name, SKU, warehouse location..." name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="inventoryTable">
                <thead>
                    <tr>
                        <th>SKU Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Current Quantity</th>
                        <th>Reorder Threshold</th>
                        <th>Unit Cost</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No inventory items registered</td>
                        </tr>
                    <?php else: foreach ($items as $item): ?>
                        <?php 
                        $statusClass = '';
                        $badge = '';
                        if ($item->quantity == 0) {
                            $statusClass = 'table-danger';
                            $badge = '<span class="badge bg-danger ms-1">OUT</span>';
                        } elseif ($item->quantity <= $item->reorder_level) {
                            $statusClass = 'table-warning-subtle';
                            $badge = '<span class="badge bg-warning text-dark ms-1">LOW</span>';
                        }
                        ?>
                        <tr class="<?= $statusClass ?>">
                            <td><a href="<?= View::url('inventory/' . $item->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($item->sku) ?></a></td>
                            <td><?= htmlspecialchars($item->name) ?> <?= $badge ?></td>
                            <td><small><?= htmlspecialchars($item->category_name ?? '-') ?></small></td>
                            <td><small class="text-monospace"><?= htmlspecialchars($item->location ?? '-') ?></small></td>
                            <td>
                                <span class="fw-bold"><?= $item->quantity ?></span> 
                                <small class="text-muted"><?= htmlspecialchars($item->unit) ?></small>
                            </td>
                            <td><small class="text-muted"><?= $item->reorder_level ?> <?= htmlspecialchars($item->unit) ?></small></td>
                            <td><small class="fw-semibold">$<?= number_format($item->unit_cost ?? 0, 2) ?></small></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= View::url('inventory/' . $item->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <a href="<?= View::url('inventory/' . $item->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        $('#inventoryTable').DataTable({
            searching: false,
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
