<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Item: <?= htmlspecialchars($item->sku) ?></h1>
    <p>Update inventory item metrics, reorder levels, location, or supplier data.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('inventory/' . $item->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Item Name *</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($item->name) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">SKU Code *</label>
                    <input type="text" class="form-control" name="sku" value="<?= htmlspecialchars($item->sku) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select Category...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $item->category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Measurement Unit</label>
                    <input type="text" class="form-control" name="unit" value="<?= htmlspecialchars($item->unit) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($item->description) ?></textarea>
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Stock Threshold Settings</h5>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Reorder Alert Level *</label>
                    <input type="number" class="form-control" name="reorder_level" value="<?= $item->reorder_level ?>" required min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Reorder Quantity</label>
                    <input type="number" class="form-control" name="reorder_quantity" value="<?= (int)($item->reorder_quantity ?? 0) ?>" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Min Allowed Stock</label>
                    <input type="number" class="form-control" name="min_quantity" value="<?= $item->min_quantity ?>" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Max Allowed Stock</label>
                    <input type="number" class="form-control" name="max_quantity" value="<?= $item->max_quantity ?>" min="0">
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Supplier & Logistics</h5>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Unit Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="unit_cost" value="<?= $item->unit_cost ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Storage Location / Rack</label>
                    <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($item->location) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Preferred Supplier</label>
                    <select class="form-select" name="supplier_id">
                        <option value="">No tracked supplier</option>
                        <?php foreach($suppliers as $supplier): ?>
                            <option value="<?= (int)$supplier->id ?>" <?= (int)($item->supplier_id ?? 0) === (int)$supplier->id ? 'selected' : '' ?>><?= htmlspecialchars($supplier->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Supplier Fallback</label>
                    <input type="text" class="form-control" name="supplier" value="<?= htmlspecialchars($item->supplier) ?>">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= (int)$item->is_active ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActive">Active item</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('inventory/' . $item->id) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
