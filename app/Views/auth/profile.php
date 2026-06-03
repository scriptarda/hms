<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>My Profile</h1>
    <p>Manage your account settings, profile information, and password security.</p>
</div>

<div class="row g-4">
    <!-- Left Column: Avatar & Account Metadata -->
    <div class="col-lg-4 col-md-5">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body py-5">
                <div class="position-relative d-inline-block mb-3">
                    <?php if (!empty($user->avatar)): ?>
                        <img src="<?= View::url('uploads/avatars/' . $user->avatar) ?>" alt="Avatar" class="rounded-circle border border-primary border-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center border border-primary border-3 mx-auto" style="width: 120px; height: 120px; font-size: 3rem; font-weight: 700;">
                            <?= htmlspecialchars(strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></h4>
                <p class="text-primary small mb-3"><span class="badge bg-primary-light text-primary"><?= htmlspecialchars($user->role_name ?? 'Staff') ?></span></p>
                <div class="border-top pt-3 text-start">
                    <div class="mb-2">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-medium text-primary-dark"><?= htmlspecialchars($user->department_name ?? 'None') ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Job Title</small>
                        <span class="fw-medium"><?= htmlspecialchars($user->job_title ?? 'Clinical Staff') ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Email Address</small>
                        <span class="fw-medium"><?= htmlspecialchars($user->email ?? '') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Settings Form -->
    <div class="col-lg-8 col-md-7">
        <!-- Profile details -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Profile Details</h5>
            </div>
            <div class="card-body">
                <form action="<?= View::url('profile') ?>" method="POST" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user->first_name ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user->last_name ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Update Profile Photo</label>
                            <input type="file" class="form-control" name="avatar" accept="image/*">
                            <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF. Max 10MB.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Security change password -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form action="<?= View::url('change-password') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                            <div class="form-text">Must be at least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning text-white"><i class="bi bi-key me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
