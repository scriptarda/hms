<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Supplier Tracking</h1><p>Maintain preferred vendors, lead times, contacts, and inventory coverage.</p></div>
    <div class="page-actions"><a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary">Dashboard</a><a href="<?= View::url('inventory/items') ?>" class="btn btn-outline-primary">Inventory List</a></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header">Add Supplier</div>
            <div class="card-body">
                <form action="<?= View::url('inventory/suppliers/store') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label">Code</label><input class="form-control" name="code"></div>
                        <div class="col-6 mb-3"><label class="form-label">Lead Days</label><input type="number" class="form-control" name="lead_time_days" value="7"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Contact</label><input class="form-control" name="contact_name"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
                    <div class="mb-3"><label class="form-label">Payment Terms</label><input class="form-control" name="payment_terms" placeholder="Net 30"></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="activeSupplier" checked><label class="form-check-label" for="activeSupplier">Active supplier</label></div>
                    <button class="btn btn-primary w-100">Save Supplier</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="supplierTable">
                        <thead><tr><th>Supplier</th><th>Contact</th><th>Lead Time</th><th>Items</th><th>Stock Value</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($supplier->name) ?></strong><br><small class="text-muted"><?= htmlspecialchars($supplier->code ?? '') ?></small></td>
                                <td><small><?= htmlspecialchars($supplier->contact_name ?: '-') ?><br><?= htmlspecialchars($supplier->email ?: $supplier->phone ?: '') ?></small></td>
                                <td><?= (int)$supplier->lead_time_days ?> days</td>
                                <td><?= (int)$supplier->item_count ?></td>
                                <td class="fw-semibold">$<?= number_format((float)$supplier->inventory_value, 2) ?></td>
                                <td><?= $supplier->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
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
<script>$(function(){ $('#supplierTable').DataTable({ pageLength:25 }); });</script>
<?php View::endSection(); ?>
