<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Create User Account</h1>
    <p>Add a new employee login to HEMS database, and configure roles and departments access.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('admin/users/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">First Name *</label>
                    <input type="text" class="form-control" name="first_name" required placeholder="e.g. John">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" required placeholder="e.g. Doe">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address (Login Username) *</label>
                    <input type="email" class="form-control" name="email" required placeholder="e.g. john.doe@healthcentral.org">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Account Password *</label>
                    <input type="password" class="form-control" name="password" required placeholder="Minimum 8 characters">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Phone Number</label>
                    <input type="text" class="form-control" name="phone" placeholder="e.g. +1 555-0199">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Job Title</label>
                    <input type="text" class="form-control" name="job_title" placeholder="e.g. Biomedical Engineer II">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Department Linkage</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">System Role Group *</label>
                    <select class="form-select" name="role_id" required>
                        <option value="">Select Role...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role->id ?>"><?= htmlspecialchars($role->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Account Login Status</label>
                    <select class="form-select" name="status" required>
                        <option value="active" selected>Active - Allow Logins</option>
                        <option value="inactive">Inactive - Block Logins</option>
                        <option value="locked">Locked - Account Suspended</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Create Account</button>
                <a href="<?= View::url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
