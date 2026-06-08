<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Models\MaintenanceTask;
$checklistText = implode("\n", array_map(fn($item) => $item['label'] ?? '', $checklist ?? []));
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Edit <?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></h1>
        <p>Update work order scope, schedule, status, technician assignment, and checklist.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance/' . $task->id) ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Details</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('maintenance/' . $task->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($task->title) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($task->description ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Linked Asset</label>
                    <select class="form-select select2" name="asset_id">
                        <option value="">No linked asset</option>
                        <?php foreach ($assets as $asset): ?>
                            <option value="<?= (int)$asset->id ?>" <?= (int)$task->asset_id === (int)$asset->id ? 'selected' : '' ?>><?= htmlspecialchars($asset->asset_tag . ' - ' . $asset->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">No department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int)$dept->id ?>" <?= (int)$task->department_id === (int)$dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Type *</label>
                    <select class="form-select" name="type" required>
                        <?php foreach (['preventive','corrective','inspection','emergency'] as $type): ?>
                            <option value="<?= $type ?>" <?= $task->type === $type ? 'selected' : '' ?>><?= ucwords($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Priority *</label>
                    <select class="form-select" name="priority" required>
                        <?php foreach (['critical','high','medium','low'] as $priority): ?>
                            <option value="<?= $priority ?>" <?= $task->priority === $priority ? 'selected' : '' ?>><?= ucwords($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status *</label>
                    <select class="form-select" name="status" required>
                        <?php foreach (['scheduled','in_progress','overdue','completed','cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $task->status === $status ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estimated Hours</label>
                    <input type="number" step="0.1" class="form-control" name="estimated_hours" value="<?= htmlspecialchars((string)($task->estimated_hours ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Scheduled Date *</label>
                    <input type="date" class="form-control" name="scheduled_date" value="<?= htmlspecialchars($task->scheduled_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Due Date *</label>
                    <input type="date" class="form-control" name="due_date" value="<?= htmlspecialchars($task->due_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Technician</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">Unassigned queue</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= (int)$tech->id ?>" <?= (int)$task->assigned_to === (int)$tech->id ? 'selected' : '' ?>><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Failure Code</label>
                    <input type="text" class="form-control" name="failure_code" value="<?= htmlspecialchars($task->failure_code ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Downtime Minutes</label>
                    <input type="number" class="form-control" name="downtime_minutes" value="<?= (int)($task->downtime_minutes ?? 0) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Checklist</label>
                    <textarea class="form-control" name="checklist" rows="4"><?= htmlspecialchars($checklistText) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($task->notes ?? '') ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('maintenance/' . $task->id) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
$(function() { initSelect2('.select2'); });
</script>
<?php View::endSection(); ?>
