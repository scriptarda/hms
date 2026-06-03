<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header">
    <h1>Role-Based Access Control (RBAC)</h1>
    <p>Manage security authorization roles and configure specific feature permission matrices.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Role Name</th>
                        <th>Identifier Slug</th>
                        <th>Role Description</th>
                        <th>Type</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary-dark"><?= htmlspecialchars($r->name) ?></td>
                            <td><small class="text-monospace text-muted"><?= htmlspecialchars($r->slug) ?></small></td>
                            <td><small><?= htmlspecialchars($r->description ?? '-') ?></small></td>
                            <td>
                                <?php if ($r->is_system): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-dark">System Built-In</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary-dark">Custom Role</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= View::url('admin/roles/' . $r->id . '/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-shield-lock me-1"></i>Edit Permissions</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
