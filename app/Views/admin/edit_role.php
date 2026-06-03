<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Permissions Matrix: <?= htmlspecialchars($role->name) ?></h1>
        <p>Assign module level authorization rules for this role group.</p>
    </div>
    <a href="<?= View::url('admin/roles') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Roles</a>
</div>

<form action="<?= View::url('admin/roles/' . $role->id . '/update') ?>" method="POST">
    <?= CSRF::field() ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent py-3">
            <h5 class="card-title fw-bold mb-0">Feature Authorization Rules</h5>
        </div>
        <div class="card-body">
            <!-- Group permissions by module -->
            <?php 
            $grouped = [];
            foreach ($permissions as $p) {
                $grouped[$p->module][] = $p;
            }
            ?>

            <div class="row g-4">
                <?php foreach ($grouped as $module => $perms): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 rounded border bg-light h-100">
                            <h6 class="fw-bold text-primary text-uppercase mb-3 border-bottom pb-2"><i class="bi bi-folder-check me-2"></i><?= htmlspecialchars(strtoupper($module)) ?></h6>
                            
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($perms as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p->id ?>" id="perm_<?= $p->id ?>" <?= in_array($p->id, $rolePerms) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="perm_<?= $p->id ?>">
                                            <strong><?= htmlspecialchars($p->name) ?></strong>
                                            <span class="d-block text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($p->description ?? '') ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer bg-transparent py-3 d-flex justify-content-end gap-2 border-top">
            <a href="<?= View::url('admin/roles') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-shield-check me-1"></i>Save Permissions Matrix</button>
        </div>
    </div>
</form>
<?php View::endSection(); ?>
