<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Create Department</h1>
    <p>Add a new department center to organize budget allocations and asset owners.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('admin/departments/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Department Name *</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Biomedical Engineering">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Department Code (Short acronym) *</label>
                    <input type="text" class="form-control" name="code" required placeholder="e.g. BME">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Budget codes, operational goals..."></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Department Head / Manager</label>
                    <select class="form-select" name="head_user_id">
                        <option value="">Select Manager...</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Contact Email</label>
                    <input type="email" class="form-control" name="email" placeholder="e.g. bme@healthcentral.org">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Extension phone</label>
                    <input type="text" class="form-control" name="phone" placeholder="e.g. ext 4210">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Create Department</button>
                <a href="<?= View::url('admin/departments') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
