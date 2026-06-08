<?php use App\Helpers\View; use App\Models\MaintenanceTask; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance Dashboard</h1>
        <p>Plan preventive maintenance, monitor corrective repairs, and keep technician workload visible.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
        <a href="<?= View::url('maintenance/queue') ?>" class="btn btn-outline-secondary"><i class="bi bi-person-workspace me-1"></i>Queue</a>
        <a href="<?= View::url('maintenance/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Schedule</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Open Work Orders</span><div class="kpi-icon blue"><i class="bi bi-wrench-adjustable"></i></div></div>
            <div class="kpi-value"><?= (int)$metrics['open'] ?></div>
            <div class="kpi-change stable">Scheduled, active, and overdue</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Due Today</span><div class="kpi-icon yellow"><i class="bi bi-calendar2-check"></i></div></div>
            <div class="kpi-value"><?= (int)$metrics['due_today'] ?></div>
            <div class="kpi-change stable">Requires review today</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card" style="border-color:#dc3545">
            <div class="kpi-header"><span class="kpi-label text-danger">Overdue</span><div class="kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div></div>
            <div class="kpi-value text-danger"><?= (int)$metrics['overdue'] ?></div>
            <a href="<?= View::url('maintenance/work-orders?status=overdue') ?>" class="small text-danger">Review backlog</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">PM Compliance</span><div class="kpi-icon green"><i class="bi bi-shield-check"></i></div></div>
            <div class="kpi-value"><?= number_format((float)$metrics['pm_compliance'], 1) ?>%</div>
            <div class="kpi-change stable"><?= (int)$metrics['active_schedules'] ?> active schedules</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Upcoming Maintenance</span>
                <a href="<?= View::url('maintenance/calendar') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-4">Work Order</th><th>Asset</th><th>Type</th><th>Due</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (empty($upcoming)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No upcoming maintenance scheduled.</td></tr>
                        <?php else: foreach ($upcoming as $task): ?>
                            <tr>
                                <td class="ps-4"><a href="<?= View::url('maintenance/' . $task->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></a><br><small><?= htmlspecialchars($task->title) ?></small></td>
                                <td><small><?= htmlspecialchars($task->asset_tag ?? '-') ?></small></td>
                                <td><span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($task->type) ?></span></td>
                                <td><small class="<?= strtotime($task->due_date) < strtotime(date('Y-m-d')) ? 'text-danger fw-bold' : 'text-muted' ?>"><?= View::date($task->due_date) ?></small></td>
                                <td><?= View::statusBadge($task->status) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header">Work Order Mix</div>
            <div class="card-body">
                <div style="height:260px"><canvas id="maintenanceTypeChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Risk Watch</span>
                <a href="<?= View::url('maintenance/work-orders?priority=critical') ?>" class="btn btn-sm btn-outline-danger">Critical</a>
            </div>
            <div class="card-body">
                <?php if (empty($atRisk)): ?>
                    <p class="text-muted small text-center mb-0">No urgent maintenance risks.</p>
                <?php else: foreach ($atRisk as $task): ?>
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                        <div>
                            <a href="<?= View::url('maintenance/' . $task->id) ?>" class="fw-semibold"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></a>
                            <div class="small text-muted"><?= htmlspecialchars($task->title) ?></div>
                        </div>
                        <div class="text-end">
                            <?= View::priorityBadge($task->priority) ?>
                            <div class="small text-muted mt-1"><?= View::date($task->due_date) ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Preventive Schedules Due Soon</span>
                <a href="<?= View::url('maintenance/create') ?>" class="btn btn-sm btn-outline-primary">New PM</a>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                    <p class="text-muted small text-center mb-0">No preventive schedules due in the next 30 days.</p>
                <?php else: foreach ($schedules as $schedule): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($schedule->asset_tag) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($schedule->title) ?> - <?= htmlspecialchars(MaintenanceTask::frequencyLabel($schedule->frequency)) ?></small>
                        </div>
                        <small class="fw-semibold"><?= View::date($schedule->next_due) ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
const typeRows = <?= json_encode($typeCounts) ?>;
new Chart(document.getElementById('maintenanceTypeChart'), {
    type: 'doughnut',
    data: {
        labels: typeRows.map(row => row.type.replace('_', ' ').toUpperCase()),
        datasets: [{
            data: typeRows.map(row => Number(row.cnt)),
            backgroundColor: ['#0d6efd', '#20c997', '#f59e0b', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php View::endSection(); ?>
