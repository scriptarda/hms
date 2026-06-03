<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Schedule Maintenance Task</h1>
    <p>Register a corrective repair or recurring preventive PM work order.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('maintenance/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-bold">Work Order Title *</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g. Annual PM Calibration or Ventilation Filter Replacement" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Detailed Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Step-by-step instructions or checklist for fulfillment..."></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Linked Asset</label>
                    <select class="form-select select2" name="asset_id">
                        <option value="">None / Search Asset Tag...</option>
                        <?php foreach ($assets as $ast): ?>
                            <option value="<?= $ast->id ?>"><?= htmlspecialchars($ast->asset_tag . ' — ' . $ast->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Responsible Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Work Order Type *</label>
                    <select class="form-select" name="type" required>
                        <option value="preventive">Preventive Maintenance (PM)</option>
                        <option value="corrective">Corrective (Repair)</option>
                        <option value="emergency">Emergency (Breakdown)</option>
                        <option value="inspection">Safety / Calibration Inspection</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Priority *</label>
                    <select class="form-select" name="priority" required>
                        <option value="low">Low - Routine</option>
                        <option value="medium" selected>Medium - Normal</option>
                        <option value="high">High - High Priority</option>
                        <option value="critical">Critical - Safety / Operational Block</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Scheduled Start Date *</label>
                    <input type="date" class="form-control" name="scheduled_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Due Date / Deadline *</label>
                    <input type="date" class="form-control" name="due_date" required value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Estimated Hours</label>
                    <input type="number" step="0.1" class="form-control" name="estimated_hours" placeholder="e.g. 2.5">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Assign Technician</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">Assign Later (Fulfillment Queue)</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech->id ?>"><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Planner Notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Any additional tools, safety instructions, or safety gear requirements..."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Schedule Task</button>
                <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-secondary">Cancel</a>
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
