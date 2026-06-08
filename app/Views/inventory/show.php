<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canManage = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician']);

View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4 text-muted">SKU: <?= htmlspecialchars($item->sku) ?></span>
            <?php if ($item->quantity == 0): ?>
                <span class="badge bg-danger">Out of Stock</span>
            <?php elseif ($item->quantity <= $item->reorder_level): ?>
                <span class="badge bg-warning text-dark">Low Stock Alert</span>
            <?php else: ?>
                <span class="badge bg-success">In Stock</span>
            <?php endif; ?>
        </div>
        <h1><?= htmlspecialchars($item->name) ?></h1>
    </div>
    <div class="page-actions">
        <?php if ($canManage): ?>
            <a href="<?= View::url('inventory/' . $item->id . '/edit') ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Item</a>
            <a href="<?= View::url('inventory/purchase-requests') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-receipt me-1"></i>Purchase Requests</a>
        <?php endif; ?>
        <a href="<?= View::url('inventory/items') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Stock List</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Item Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Stock Specifications</h5>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Current Stock Level</small>
                        <span class="fw-bold fs-5 text-primary-dark"><?= $item->quantity ?> <?= htmlspecialchars($item->unit) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Reorder Warning Level</small>
                        <span class="fw-semibold"><?= $item->reorder_level ?> <?= htmlspecialchars($item->unit) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Unit Cost</small>
                        <span class="fw-semibold text-success">$<?= number_format($item->unit_cost ?? 0, 2) ?></span>
                    </div>
                    
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Category</small>
                        <span><?= htmlspecialchars($item->category_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Storage Location</small>
                        <span><?= htmlspecialchars($item->location ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Primary Supplier</small>
                        <span><?= htmlspecialchars($item->display_supplier ?? '-') ?></span>
                        <?php if (!empty($item->supplier_email)): ?><small class="text-muted d-block"><?= htmlspecialchars($item->supplier_email) ?></small><?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <small class="text-muted d-block">Minimum Safety Stock</small>
                        <span><?= $item->min_quantity ?> <?= htmlspecialchars($item->unit) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Maximum Storage Level</small>
                        <span><?= $item->max_quantity ?> <?= htmlspecialchars($item->unit) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Reorder Quantity</small>
                        <span><?= (int)($item->reorder_quantity ?? 0) ?> <?= htmlspecialchars($item->unit) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Last Restocked</small>
                        <span><?= !empty($item->last_restocked_at) ? View::date($item->last_restocked_at, 'M d, Y H:i') : '-' ?></span>
                    </div>
                </div>

                <?php if (!empty($item->description)): ?>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <small class="text-muted d-block fw-bold mb-1">Specification Details / Device Compatibility</small>
                        <p class="mb-0 small text-primary-dark whitespace-pre-wrap"><?= htmlspecialchars($item->description) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Purchase Requests</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Request</th><th>Status</th><th>Qty</th><th>Needed By</th><th>Requested By</th></tr></thead>
                        <tbody>
                        <?php if (empty($purchaseRequests)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3 small">No purchase requests for this item.</td></tr>
                        <?php else: foreach ($purchaseRequests as $request): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= htmlspecialchars($request->request_number) ?></td>
                                <td><?= View::statusBadge($request->status) ?></td>
                                <td><?= (int)$request->quantity ?> <?= htmlspecialchars($item->unit) ?></td>
                                <td><small><?= View::date($request->needed_by) ?></small></td>
                                <td><small><?= htmlspecialchars($request->requested_by_name) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transactions History -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Stock Movement Ledger</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Quantity Change</th>
                                <th>Notes / Reference</th>
                                <th>Logged By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3 small">No stock changes logged.</td>
                                </tr>
                            <?php else: foreach ($transactions as $tx): ?>
                                <?php
                                $typeLabel = '';
                                if ($tx->type === 'in' || $tx->type === 'return') {
                                    $typeLabel = '<span class="badge bg-success-subtle text-success text-uppercase">' . $tx->type . '</span>';
                                    $qtyLabel = '<span class="text-success fw-bold">+' . $tx->quantity . '</span>';
                                } else {
                                    $typeLabel = '<span class="badge bg-danger-subtle text-danger text-uppercase">' . $tx->type . '</span>';
                                    $qtyLabel = '<span class="text-danger fw-bold">-' . $tx->quantity . '</span>';
                                }
                                ?>
                                <tr>
                                    <td><?= $typeLabel ?></td>
                                    <td><?= $qtyLabel ?></td>
                                    <td><small><?= htmlspecialchars($tx->notes ?? '-') ?></small></td>
                                    <td><small><?= htmlspecialchars($tx->user_name) ?></small></td>
                                    <td><small class="text-muted"><?= View::date($tx->created_at, 'M d, Y H:i') ?></small></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Actions -->
    <div class="col-lg-4">
        <?php if ($canManage): ?>
            <!-- Stock transaction entry -->
            <div class="card shadow-sm border-0 mb-4 border-start border-primary border-3">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Record Stock Movement</h6>
                </div>
                <div class="card-body">
                    <form action="<?= View::url('inventory/' . $item->id . '/transaction') ?>" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Transaction Type *</label>
                            <select class="form-select form-select-sm" name="type" required>
                                <option value="in">Stock In (Purchase/Restock)</option>
                                <option value="out">Stock Out (Usage/Deployment)</option>
                                <option value="return">Stock Return (Reclaimed from Ward)</option>
                                <option value="adjustment">Stock Adjustment (Audit Discrepancy)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Quantity *</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="quantity" min="1" required placeholder="0">
                                <span class="input-group-text"><?= htmlspecialchars($item->unit) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Movement Notes *</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="3" required placeholder="e.g. Deployed 2x sensors for preventive maintenance work order #WO-0021"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-arrow-left-right me-1"></i>Process Transaction</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 border-start border-success border-3">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0 text-success"><i class="bi bi-receipt-cutoff me-2"></i>Create Purchase Request</h6>
                </div>
                <div class="card-body">
                    <form action="<?= View::url('inventory/purchase-requests/store') ?>" method="POST">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="item_id" value="<?= (int)$item->id ?>">
                        <input type="hidden" name="supplier_id" value="<?= (int)($item->supplier_id ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Quantity</label>
                            <input type="number" class="form-control form-control-sm" name="quantity" min="1" value="<?= (int)($item->reorder_quantity ?: max($item->reorder_level - $item->quantity, 1)) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Needed By</label>
                            <input type="date" class="form-control form-control-sm" name="needed_by" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Notes</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Reason, vendor quote, or maintenance reference"></textarea>
                        </div>
                        <button class="btn btn-success btn-sm w-100"><i class="bi bi-send me-1"></i>Submit Request</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
