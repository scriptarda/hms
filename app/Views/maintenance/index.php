<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance Work Orders</h1>
        <p>Schedule and track preventive calibration, routine safety inspections, and repair work orders.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance/calendar') ?>" class="btn btn-outline-primary"><i class="bi bi-calendar3 me-1"></i>Calendar View</a>
        <a href="<?= View::url('maintenance/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Schedule Task</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="<?= View::url('maintenance') ?>" class="row g-2 mb-4">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="preventive" <?= ($filters['type'] ?? '') === 'preventive' ? 'selected' : '' ?>>Preventive PM</option>
                    <option value="corrective" <?= ($filters['type'] ?? '') === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                    <option value="emergency" <?= ($filters['type'] ?? '') === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                    <option value="inspection" <?= ($filters['type'] ?? '') === 'inspection' ? 'selected' : '' ?>>Inspection</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="priority" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="critical" <?= ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                    <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Apply Filter</button>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="maintenanceTable">
                <thead>
                    <tr>
                        <th>WO ID</th>
                        <th>Task Title</th>
                        <th>Linked Asset</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Scheduled Date</th>
                        <th>Due Date</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No work orders scheduled</td>
                        </tr>
                    <?php else: foreach ($tasks as $t): ?>
                        <tr>
                            <td><a href="<?= View::url('maintenance/' . $t->id) ?>" class="fw-bold text-primary">#WO-<?= str_pad($t->id, 4, '0', STR_PAD_LEFT) ?></a></td>
                            <td><?= htmlspecialchars($t->title) ?></td>
                            <td>
                                <?php if ($t->asset_id): ?>
                                    <a href="<?= View::url('assets/' . $t->asset_id) ?>" class="fw-medium"><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($t->asset_tag) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-uppercase fw-semibold"><?= htmlspecialchars($t->type) ?></small></td>
                            <td><?= View::priorityBadge($t->priority) ?></td>
                            <td><small><?= View::date($t->scheduled_date) ?></small></td>
                            <td>
                                <small class="<?= (strtotime($t->due_date) < time() && $t->status !== 'completed') ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    <?= View::date($t->due_date) ?>
                                </small>
                            </td>
                            <td><small><?= htmlspecialchars($t->tech_name ?? 'Queue') ?></small></td>
                            <td><?= View::statusBadge($t->status) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= View::url('maintenance/' . $t->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <a href="<?= View::url('maintenance/' . $t->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                </div>
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
        $('#maintenanceTable').DataTable({
            searching: true,
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
