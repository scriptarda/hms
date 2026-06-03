<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit User: <?= htmlspecialchars(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></h1>
    <p>Update system access details, change passwords, and configure departments ownership.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('admin/users/' . $user->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">First Name *</label>
                    <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user->first_name ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user->last_name ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address *</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Reset Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to retain current password">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Phone Number</label>
                    <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Job Title</label>
                    <input type="text" class="form-control" name="job_title" value="<?= htmlspecialchars($user->job_title ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Department Linkage</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= $user->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">System Role Group *</label>
                    <select class="form-select" name="role_id" required>
                        <option value="">Select Role...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role->id ?>" <?= $user->role_id == $role->id ? 'selected' : '' ?>><?= htmlspecialchars($role->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Account Status</label>
                    <select class="form-select" name="status" required>
                        <option value="active" <?= $user->status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="locked" <?= $user->status === 'locked' ? 'selected' : '' ?>>Locked</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
