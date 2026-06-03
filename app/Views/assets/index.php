<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Asset Registry</h1>
        <p>Monitor, track, and manage all hospital assets and clinical equipment.</p>
    </div>
    <a href="<?= View::url('assets/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Register Asset</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="<?= View::url('assets') ?>" class="row g-2 mb-4">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status" id="filterStatus" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="maintenance" <?= ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    <option value="retired" <?= ($filters['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
                    <option value="disposed" <?= ($filters['status'] ?? '') === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="category_id" id="filterCategory" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="department_id" id="filterDept" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept->id ?>" <?= ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Search name, tag, SN..." name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="assetsTable">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Manufacturer / Model</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Warranty Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No assets found</td>
                        </tr>
                    <?php else: foreach ($assets as $a): ?>
                        <tr>
                            <td><a href="<?= View::url('assets/' . $a->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($a->asset_tag) ?></a></td>
                            <td><?= htmlspecialchars($a->name) ?></td>
                            <td><small><?= htmlspecialchars($a->category_name ?? '-') ?></small></td>
                            <td><small><?= htmlspecialchars(($a->manufacturer ?? '') . ' / ' . ($a->model ?? '')) ?></small></td>
                            <td><small><?= htmlspecialchars($a->department_name ?? '-') ?></small></td>
                            <td><?= View::statusDot($a->status) ?></td>
                            <td>
                                <small class="<?= (strtotime($a->warranty_expiry) < time() && $a->warranty_expiry) ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    <?= $a->warranty_expiry ? View::date($a->warranty_expiry) : '-' ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= View::url('assets/' . $a->id) ?>" class="btn btn-sm btn-outline-primary" title="View details"><i class="bi bi-eye"></i></a>
                                    <a href="<?= View::url('assets/' . $a->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
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
        $('#assetsTable').DataTable({
            searching: false,
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
