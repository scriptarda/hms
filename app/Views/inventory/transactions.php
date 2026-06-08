<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Inventory Transactions</h1><p>Audit every stock movement across spare parts and consumables.</p></div>
    <div class="page-actions"><a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary">Dashboard</a><a href="<?= View::url('inventory/items') ?>" class="btn btn-outline-primary">Inventory List</a></div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="txTable">
                <thead><tr><th>Date</th><th>SKU</th><th>Item</th><th>Type</th><th>Quantity</th><th>Reference</th><th>User</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><small><?= View::date($tx->created_at, 'M d, Y H:i') ?></small></td>
                        <td><a href="<?= View::url('inventory/' . $tx->item_id) ?>" class="fw-bold"><?= htmlspecialchars($tx->sku) ?></a></td>
                        <td><?= htmlspecialchars($tx->item_name) ?></td>
                        <td><span class="badge <?= in_array($tx->type, ['in','return'], true) ? 'bg-success' : 'bg-danger' ?>"><?= strtoupper(htmlspecialchars($tx->type)) ?></span></td>
                        <td><?= (int)$tx->quantity ?> <?= htmlspecialchars($tx->unit) ?></td>
                        <td><small><?= htmlspecialchars(trim(($tx->reference_type ?? '') . ' #' . ($tx->reference_id ?? ''), ' #') ?: '-') ?></small></td>
                        <td><small><?= htmlspecialchars($tx->user_name) ?></small></td>
                        <td><small><?= htmlspecialchars($tx->notes ?? '-') ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>$(function(){ $('#txTable').DataTable({ order:[[0,'desc']], pageLength:25 }); });</script>
<?php View::endSection(); ?>
