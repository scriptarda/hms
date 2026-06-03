<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>User Account Management</h1>
        <p>Configure user roles, update department linkages, and de-activate employee log-ins.</p>
    </div>
    <a href="<?= View::url('admin/users/create') ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create New User</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="GET" action="<?= View::url('admin/users') ?>" class="row g-2 mb-4">
            <div class="col-md-9">
                <input type="text" class="form-control form-control-sm" placeholder="Search first name, last name, job title, email..." name="search" value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Email Address</th>
                        <th>Role Group</th>
                        <th>Department</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No users matching search filters found.</td>
                        </tr>
                    <?php else: foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-bold text-primary-dark"><?= htmlspecialchars(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?></td>
                            <td><small><?= htmlspecialchars($u->email) ?></small></td>
                            <td><span class="badge bg-primary-light text-primary"><?= htmlspecialchars($u->role_name ?? 'Staff') ?></span></td>
                            <td><small><?= htmlspecialchars($u->dept_name ?? '-') ?></small></td>
                            <td><small><?= htmlspecialchars($u->job_title ?? '-') ?></small></td>
                            <td><?= View::statusDot($u->status) ?></td>
                            <td><small class="text-muted"><?= $u->last_login_at ? View::date($u->last_login_at, 'M d, H:i') : 'Never' ?></small></td>
                            <td>
                                <a href="<?= View::url('admin/users/' . $u->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Edit account details"><i class="bi bi-pencil"></i></a>
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
        $('#usersTable').DataTable({
            searching: false,
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
