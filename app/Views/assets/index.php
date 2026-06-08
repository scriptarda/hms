<?php use App\Helpers\View; use App\Models\Asset; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Asset Registry</h1>
        <p>Track asset IDs, ownership, location, warranty coverage, and lifecycle status.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('qr/scan') ?>" target="_blank" class="btn btn-outline-primary"><i class="bi bi-qr-code-scan me-1"></i>Scan QR</a>
        <a href="<?= View::url('assets/qr/labels') ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>QR Labels</a>
        <a href="<?= View::url('assets/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Register Asset</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Total Assets</div><div class="kpi-value"><?= (int)$metrics['total'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Active</div><div class="kpi-value"><?= (int)$metrics['active'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Assigned</div><div class="kpi-value"><?= (int)$metrics['assigned'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Warranty Alerts</div><div class="kpi-value"><?= (int)$metrics['warranty_alerts'] ?></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="<?= View::url('assets') ?>" class="row g-2 mb-4">
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <?php foreach (['active', 'inactive', 'maintenance', 'retired', 'disposed'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= Asset::statusLabel($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="category_id" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="department_id" onchange="this.form.submit()">
                    <option value="">All departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept->id ?>" <?= ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="assigned_user_id" onchange="this.form.submit()">
                    <option value="">Any owner</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user->id ?>" <?= ($filters['assigned_user_id'] ?? '') == $user->id ? 'selected' : '' ?>><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="warranty" onchange="this.form.submit()">
                    <option value="">Any warranty</option>
                    <option value="expired" <?= ($filters['warranty'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="expiring_30" <?= ($filters['warranty'] ?? '') === 'expiring_30' ? 'selected' : '' ?>>Expiring 30d</option>
                    <option value="expiring_90" <?= ($filters['warranty'] ?? '') === 'expiring_90' ? 'selected' : '' ?>>Expiring 90d</option>
                    <option value="missing" <?= ($filters['warranty'] ?? '') === 'missing' ? 'selected' : '' ?>>Missing date</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Search..." name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="assetsTable">
                <thead>
                    <tr>
                        <th>Asset ID</th>
                        <th>Asset Tag</th>
                        <th>Asset</th>
                        <th>Serial / Model</th>
                        <th>Assigned User</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Warranty</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No assets found</td></tr>
                <?php else: foreach ($assets as $a): $warranty = Asset::warrantyState($a->warranty_expiry ?? null); ?>
                    <tr>
                        <td><small class="text-muted">#<?= (int)$a->id ?></small></td>
                        <td><a href="<?= View::url('assets/' . $a->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($a->asset_tag) ?></a></td>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($a->name) ?></span>
                            <small class="d-block text-muted"><?= htmlspecialchars($a->category_name ?? '-') ?></small>
                        </td>
                        <td>
                            <small class="d-block"><?= htmlspecialchars($a->serial_number ?: '-') ?></small>
                            <small class="text-muted"><?= htmlspecialchars(trim(($a->manufacturer ?? '') . ' ' . ($a->model ?? '')) ?: '-') ?></small>
                        </td>
                        <td>
                            <?php if ($a->assigned_user_name): ?>
                                <small class="fw-semibold"><?= htmlspecialchars($a->assigned_user_name) ?></small>
                                <small class="d-block text-muted"><?= htmlspecialchars($a->assigned_user_email) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Unassigned</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?= htmlspecialchars($a->department_name ?? '-') ?></small></td>
                        <td><?= View::statusDot($a->status) ?></td>
                        <td>
                            <span class="badge bg-<?= $warranty['class'] ?>"><?= htmlspecialchars($warranty['label']) ?></span>
                            <small class="d-block text-muted"><?= $a->warranty_expiry ? View::date($a->warranty_expiry) : '-' ?></small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= View::url('assets/' . $a->id) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= View::url('assets/' . $a->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="<?= View::url('assets/' . $a->id . '/qr') ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="QR"><i class="bi bi-qr-code"></i></a>
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
    $('#assetsTable').DataTable({ searching: false, ordering: true, info: true, paging: true });
});
</script>
<?php View::endSection(); ?>
