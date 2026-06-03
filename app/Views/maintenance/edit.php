<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Work Order: #WO-<?= str_pad($task->id, 4, '0', STR_PAD_LEFT) ?></h1>
    <p>Update task scheduling, assignments, and specification parameters.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('maintenance/' . $task->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-bold">Work Order Title *</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($task->title) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Detailed Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($task->description) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Linked Asset</label>
                    <select class="form-select select2" name="asset_id">
                        <option value="">None / Search Asset Tag...</option>
                        <?php foreach ($assets as $ast): ?>
                            <option value="<?= $ast->id ?>" <?= $task->asset_id == $ast->id ? 'selected' : '' ?>><?= htmlspecialchars($ast->asset_tag . ' — ' . $ast->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Responsible Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= $task->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Work Order Type *</label>
                    <select class="form-select" name="type" required>
                        <option value="preventive" <?= $task->type === 'preventive' ? 'selected' : '' ?>>Preventive PM</option>
                        <option value="corrective" <?= $task->type === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                        <option value="emergency" <?= $task->type === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                        <option value="inspection" <?= $task->type === 'inspection' ? 'selected' : '' ?>>Inspection</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Priority *</label>
                    <select class="form-select" name="priority" required>
                        <option value="low" <?= $task->priority === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $task->priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $task->priority === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="critical" <?= $task->priority === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="scheduled" <?= $task->status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="in_progress" <?= $task->status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $task->status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="overdue" <?= $task->status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        <option value="cancelled" <?= $task->status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Scheduled Start Date *</label>
                    <input type="date" class="form-control" name="scheduled_date" value="<?= $task->scheduled_date ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Due Date / Deadline *</label>
                    <input type="date" class="form-control" name="due_date" value="<?= $task->due_date ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Estimated Hours</label>
                    <input type="number" step="0.1" class="form-control" name="estimated_hours" value="<?= $task->estimated_hours ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Assign Technician</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech->id ?>" <?= $task->assigned_to == $tech->id ? 'selected' : '' ?>><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Planner Notes</label>
                    <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($task->notes) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('maintenance/' . $task->id) ?>" class="btn btn-outline-secondary">Cancel</a>
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
