<?php use App\Helpers\View; use App\Models\MaintenanceTask; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance Work Orders</h1>
        <p>Track preventive, corrective, inspection, and emergency maintenance from scheduling through closure.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a href="<?= View::url('maintenance/history') ?>" class="btn btn-outline-primary"><i class="bi bi-clock-history me-1"></i>History</a>
        <a href="<?= View::url('maintenance/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Schedule</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= View::url('maintenance/work-orders') ?>" class="row g-2">
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['scheduled','in_progress','overdue','completed','cancelled'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="type">
                    <option value="">All Types</option>
                    <?php foreach (['preventive','corrective','emergency','inspection'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= ucwords($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="priority">
                    <option value="">All Priorities</option>
                    <?php foreach (['critical','high','medium','low'] as $priority): ?>
                        <option value="<?= $priority ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>><?= ucwords($priority) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="assigned_to">
                    <option value="">All Technicians</option>
                    <?php foreach (($formData['technicians'] ?? []) as $tech): ?>
                        <option value="<?= (int)$tech->id ?>" <?= (string)($filters['assigned_to'] ?? '') === (string)$tech->id ? 'selected' : '' ?>><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input class="form-control form-control-sm" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="WO, asset, title">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="maintenanceTable">
                <thead>
                    <tr>
                        <th>Work Order</th>
                        <th>Task</th>
                        <th>Asset</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Due</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No maintenance work orders found.</td></tr>
                    <?php else: foreach ($tasks as $task): ?>
                        <tr>
                            <td><a href="<?= View::url('maintenance/' . $task->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></a></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($task->title) ?></div>
                                <small class="text-muted"><?= View::date($task->scheduled_date) ?></small>
                            </td>
                            <td>
                                <?php if ($task->asset_id): ?>
                                    <a href="<?= View::url('assets/' . $task->asset_id) ?>" class="fw-medium"><?= htmlspecialchars($task->asset_tag) ?></a>
                                    <small class="d-block text-muted"><?= htmlspecialchars($task->asset_name ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted">No linked asset</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($task->type) ?></span></td>
                            <td><?= View::priorityBadge($task->priority) ?></td>
                            <td><small class="<?= strtotime($task->due_date) < strtotime(date('Y-m-d')) && !in_array($task->status, ['completed','cancelled'], true) ? 'text-danger fw-bold' : 'text-muted' ?>"><?= View::date($task->due_date) ?></small></td>
                            <td><small><?= htmlspecialchars($task->tech_name ?: 'Unassigned') ?></small></td>
                            <td><?= View::statusBadge($task->status) ?></td>
                            <td class="text-end">
                                <a href="<?= View::url('maintenance/' . $task->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="<?= View::url('maintenance/' . $task->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
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
$(function() {
    $('#maintenanceTable').DataTable({ order: [[5, 'asc']], pageLength: 25 });
});
</script>
<?php View::endSection(); ?>
