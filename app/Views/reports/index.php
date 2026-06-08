<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content');
$routeFor = static fn(string $type): string => $type === 'user_activity' ? 'user-activity' : $type;
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Enterprise Reports</h1>
        <p>Operational reporting with filters, KPI cards, charts, exports, and scheduled delivery.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports/user-activity') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-lines-fill me-1"></i>User Activity</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Tickets</span><div class="kpi-icon blue"><i class="bi bi-ticket-detailed"></i></div></div><div class="kpi-value"><?= (int)$stats['tickets_count'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Assets</span><div class="kpi-icon green"><i class="bi bi-hdd-stack"></i></div></div><div class="kpi-value"><?= (int)$stats['assets_count'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Maintenance Cost</span><div class="kpi-icon yellow"><i class="bi bi-cash-stack"></i></div></div><div class="kpi-value" style="font-size:1.5rem">$<?= number_format((float)$stats['maintenance_cost'], 0) ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Scheduled</span><div class="kpi-icon blue"><i class="bi bi-calendar2-week"></i></div></div><div class="kpi-value"><?= (int)$stats['scheduled_reports'] ?></div></div></div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($reportTypes as $type => $meta): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= htmlspecialchars($meta['icon']) ?> text-<?= htmlspecialchars($meta['color']) ?> fs-3"></i>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($meta['label']) ?></h5>
                        </div>
                        <p class="text-muted small mb-0">Filtered KPI cards, charts, details, PDF, Excel, CSV, and scheduling.</p>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <a href="<?= View::url('reports/' . $routeFor($type)) ?>" class="btn btn-sm btn-primary flex-fill">Open</a>
                        <a href="<?= View::url('reports/export/' . $type . '?format=pdf') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="<?= View::url('reports/export/' . $type . '?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header">Schedule Report</div>
            <div class="card-body">
                <form action="<?= View::url('reports/schedules/store') ?>" method="POST" class="row g-3">
                    <?= CSRF::field() ?>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Weekly SLA Performance">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Report</label>
                        <select name="report_type" class="form-select">
                            <?php foreach ($reportTypes as $type => $meta): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($meta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Format</label>
                        <select name="format" class="form-select">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Recipients</label>
                        <input type="text" name="recipients" class="form-control" placeholder="ops@example.org">
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="scheduleActive" checked>
                            <label class="form-check-label small" for="scheduleActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card shadow-sm border-0">
            <div class="card-header">Scheduled Reports</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-4">Name</th><th>Report</th><th>Frequency</th><th>Next Run</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No scheduled reports yet.</td></tr>
                        <?php else: foreach ($schedules as $schedule): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= htmlspecialchars($schedule->name) ?></td>
                                <td><?= htmlspecialchars($reportTypes[$schedule->report_type]['label'] ?? $schedule->report_type) ?></td>
                                <td><?= htmlspecialchars(ucfirst($schedule->frequency)) ?> · <?= strtoupper($schedule->format) ?></td>
                                <td><small><?= View::date($schedule->next_run_at, 'M d, Y H:i') ?></small></td>
                                <td><?= View::statusBadge((int)$schedule->is_active ? 'active' : 'inactive') ?></td>
                                <td class="text-end pe-4">
                                    <form action="<?= View::url('reports/schedules/' . $schedule->id . '/toggle') ?>" method="POST">
                                        <?= CSRF::field() ?>
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int)$schedule->is_active ? 'Pause' : 'Resume' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
