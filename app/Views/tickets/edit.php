<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Ticket #<?= htmlspecialchars($ticket->ticket_number) ?></h1>
    <p>Modify general information for this ticket.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('tickets/' . $ticket->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($ticket->title) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="5" required><?= htmlspecialchars($ticket->description) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select Category...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $ticket->category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Priority</label>
                    <select class="form-select" name="priority" required>
                        <option value="low" <?= $ticket->priority === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $ticket->priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $ticket->priority === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="critical" <?= $ticket->priority === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= $ticket->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Linked Asset</label>
                    <select class="form-select select2" name="asset_id">
                        <option value="">None / Search Asset Tag...</option>
                        <?php foreach($assets as $ast): ?>
                            <option value="<?= $ast->id ?>" <?= $ticket->asset_id == $ast->id ? 'selected' : '' ?>><?= htmlspecialchars($ast->asset_tag . ' — ' . $ast->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('tickets/' . $ticket->id) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        initSelect2('.select2');
    });
</script>
<?php View::endSection(); ?>
