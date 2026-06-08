<?php use App\Helpers\View; use App\Helpers\CSRF;
$selectedType = $_GET['type'] ?? 'preventive';
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Schedule Maintenance</h1>
        <p>Create preventive schedules, corrective repairs, inspections, and emergency work orders.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
        <a href="<?= View::url('maintenance/calendar') ?>" class="btn btn-outline-secondary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>
    </div>
</div>

<form action="<?= View::url('maintenance/store') ?>" method="POST">
    <?= CSRF::field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header">Work Order Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Title *</label>
                            <input type="text" class="form-control" name="title" placeholder="Annual PM calibration, pump repair, safety inspection" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Scope, symptoms, safety constraints, expected checks, or repair instructions."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Linked Asset</label>
                            <select class="form-select select2" name="asset_id">
                                <option value="">No linked asset</option>
                                <?php foreach ($assets as $asset): ?>
                                    <option value="<?= (int)$asset->id ?>"><?= htmlspecialchars($asset->asset_tag . ' - ' . $asset->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department</label>
                            <select class="form-select" name="department_id">
                                <option value="">No department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int)$dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Type *</label>
                            <select class="form-select" name="type" id="maintenanceType" required>
                                <?php foreach (['preventive' => 'Preventive PM', 'corrective' => 'Corrective Repair', 'inspection' => 'Inspection', 'emergency' => 'Emergency'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Priority *</label>
                            <select class="form-select" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estimated Hours</label>
                            <input type="number" step="0.1" class="form-control" name="estimated_hours" placeholder="2.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Scheduled Date *</label>
                            <input type="date" class="form-control" name="scheduled_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Due Date *</label>
                            <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Assign Technician</label>
                            <select class="form-select" name="assigned_to">
                                <option value="">Unassigned queue</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= (int)$tech->id ?>"><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Failure Code</label>
                            <input type="text" class="form-control" name="failure_code" placeholder="Optional for corrective work">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Checklist</label>
                            <textarea class="form-control" name="checklist" rows="4" placeholder="One checklist item per line"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Planner Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Tools, access requirements, vendor reference, safety notes."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">Preventive Schedule</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_recurring" value="1" id="isRecurring" <?= $selectedType === 'preventive' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isRecurring">Create recurring PM schedule</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Frequency</label>
                        <select class="form-select" name="frequency">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly" selected>Quarterly</option>
                            <option value="semi_annual">Semi-Annual</option>
                            <option value="annual">Annual</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Biweekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lead Time Days</label>
                        <input type="number" min="0" class="form-control" name="lead_time_days" value="7">
                    </div>
                    <div class="alert alert-info small mb-0">Recurring settings are saved when the work order type is preventive and an asset is selected.</div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body d-grid gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Create Work Order</button>
                    <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
$(function() {
    initSelect2('.select2');
    $('#maintenanceType').on('change', function() {
        $('#isRecurring').prop('checked', this.value === 'preventive');
    });
});
</script>
<?php View::endSection(); ?>
