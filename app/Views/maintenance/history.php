<?php use App\Helpers\View; use App\Models\MaintenanceTask; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance History</h1>
        <p>Review completed and cancelled work orders, service costs, labor hours, and asset service records.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
        <a href="<?= View::url('maintenance/queue') ?>" class="btn btn-outline-secondary"><i class="bi bi-person-workspace me-1"></i>Queue</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= View::url('maintenance/history') ?>" class="row g-2">
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="type">
                    <option value="">All Types</option>
                    <?php foreach (['preventive','corrective','emergency','inspection'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= ucwords($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="asset_id">
                    <option value="">All Assets</option>
                    <?php foreach (($formData['assets'] ?? []) as $asset): ?>
                        <option value="<?= (int)$asset->id ?>" <?= (string)($filters['asset_id'] ?? '') === (string)$asset->id ? 'selected' : '' ?>><?= htmlspecialchars($asset->asset_tag) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"></div>
            <div class="col-md-2"><input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"></div>
            <div class="col-md-2"><input class="form-control form-control-sm" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search service history"></div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= View::url('maintenance/history') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="historyTable">
                <thead>
                    <tr>
                        <th>Work Order</th>
                        <th>Asset</th>
                        <th>Type</th>
                        <th>Completed</th>
                        <th>Technician</th>
                        <th>Labor</th>
                        <th>Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No service history found.</td></tr>
                <?php else: foreach ($tasks as $task): ?>
                    <tr>
                        <td><a href="<?= View::url('maintenance/' . $task->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></a><br><small><?= htmlspecialchars($task->title) ?></small></td>
                        <td><small><?= htmlspecialchars($task->asset_tag ?? '-') ?></small></td>
                        <td><span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($task->type) ?></span></td>
                        <td><small><?= View::date($task->completed_date ?: $task->updated_at) ?></small></td>
                        <td><small><?= htmlspecialchars($task->completed_by_name ?: ($task->tech_name ?: '-')) ?></small></td>
                        <td><span class="fw-semibold"><?= $task->actual_hours ? number_format((float)$task->actual_hours, 1) . 'h' : '-' ?></span></td>
                        <td><span class="fw-semibold text-success">$<?= number_format((float)($task->cost ?? 0), 2) ?></span></td>
                        <td><?= View::statusBadge($task->status) ?></td>
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
    $('#historyTable').DataTable({ order: [[3, 'desc']], pageLength: 25 });
});
</script>
<?php View::endSection(); ?>
