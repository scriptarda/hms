<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Register Stock Item</h1>
    <p>Add replacement parts, network cables, consumable reagents, or spare components to inventory database.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('inventory/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Item Name *</label>
                    <input type="text" class="form-control" name="name" placeholder="e.g. Ventilator O2 Sensor" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">SKU Code (Unique ID) *</label>
                    <input type="text" class="form-control" name="sku" placeholder="e.g. SP-VNT-O2S" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select Category...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Measurement Unit</label>
                    <input type="text" class="form-control" name="unit" value="pcs" placeholder="e.g. pcs, liters, boxes, meters">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Technical specifications, compatible device list..."></textarea>
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Stock Threshold Settings</h5>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Initial Quantity *</label>
                    <input type="number" class="form-control" name="quantity" value="0" required min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Reorder Alert Level *</label>
                    <input type="number" class="form-control" name="reorder_level" value="5" required min="0">
                    <div class="form-text small">System triggers notification when stock is equal or lower than this.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Min Allowed Stock</label>
                    <input type="number" class="form-control" name="min_quantity" value="0" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Max Allowed Stock</label>
                    <input type="number" class="form-control" name="max_quantity" value="100" min="0">
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Supplier & Logistics</h5>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Unit Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="unit_cost" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Storage Location / Rack</label>
                    <input type="text" class="form-control" name="location" placeholder="e.g. Warehouse A-2, Row 4">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Supplier Info</label>
                    <input type="text" class="form-control" name="supplier" placeholder="e.g. Hamilton Medical Supply Inc">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Add Stock Item</button>
                <a href="<?= View::url('inventory') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
