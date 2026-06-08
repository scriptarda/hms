<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Purchase Requests</h1><p>Create, approve, order, and receive inventory replenishment requests.</p></div>
    <div class="page-actions"><a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary">Dashboard</a><a href="<?= View::url('inventory/reorder-alerts') ?>" class="btn btn-outline-danger">Reorder Alerts</a></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header">New Purchase Request</div>
            <div class="card-body">
                <form action="<?= View::url('inventory/purchase-requests/store') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Item *</label>
                        <select class="form-select select2" name="item_id" required>
                            <option value="">Select SKU</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= (int)$item->id ?>" <?= (string)($filters['item_id'] ?? '') === (string)$item->id ? 'selected' : '' ?>><?= htmlspecialchars($item->sku . ' - ' . $item->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id">
                            <option value="">Use item supplier</option>
                            <?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier->id ?>"><?= htmlspecialchars($supplier->name) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label">Quantity *</label><input type="number" class="form-control" name="quantity" min="1" required></div>
                        <div class="col-6 mb-3"><label class="form-label">Unit Cost</label><input type="number" step="0.01" class="form-control" name="unit_cost"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Needed By</label><input type="date" class="form-control" name="needed_by"></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                    <button class="btn btn-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <form method="GET" action="<?= View::url('inventory/purchase-requests') ?>" class="row g-2">
                    <div class="col-md-8">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['submitted','approved','ordered','received','rejected','cancelled'] as $status): ?><option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucwords($status) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-grid"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="prTable">
                        <thead><tr><th>Request</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th>Needed</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($request->request_number) ?></strong><br><small class="text-muted"><?= htmlspecialchars($request->requested_by_name) ?></small></td>
                                <td><?= htmlspecialchars($request->sku) ?><br><small class="text-muted"><?= htmlspecialchars($request->supplier_name ?: '-') ?></small></td>
                                <td><?= (int)$request->quantity ?> <?= htmlspecialchars($request->unit) ?></td>
                                <td class="fw-semibold">$<?= number_format((float)($request->total_cost ?? 0), 2) ?></td>
                                <td><?= View::statusBadge($request->status) ?></td>
                                <td><small><?= View::date($request->needed_by) ?></small></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php foreach (['approved' => 'Approve', 'ordered' => 'Order', 'received' => 'Receive', 'rejected' => 'Reject', 'cancelled' => 'Cancel'] as $status => $label): ?>
                                            <?php if ($request->status !== $status && !in_array($request->status, ['received','cancelled','rejected'], true)): ?>
                                                <form action="<?= View::url('inventory/purchase-requests/' . $request->id . '/status') ?>" method="POST">
                                                    <?= CSRF::field() ?><input type="hidden" name="status" value="<?= $status ?>">
                                                    <button class="btn btn-sm <?= $status === 'received' ? 'btn-success' : 'btn-outline-secondary' ?>"><?= $label ?></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>$(function(){ initSelect2('.select2'); $('#prTable').DataTable({ order:[[0,'desc']], pageLength:25 }); });</script>
<?php View::endSection(); ?>
