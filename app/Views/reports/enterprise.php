<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content');
$type = $report['type'];
$filters = $report['filters'];
$exportBase = static fn(string $format): string => View::url('reports/export/' . $type . '?' . http_build_query(array_merge($filters, ['format' => $format])));
$categoryOptions = $type === 'assets' ? $filterOptions['assetCategories'] : ($type === 'inventory' ? $filterOptions['inventoryCategories'] : $filterOptions['ticketCategories']);
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><?= htmlspecialchars($report['title']) ?></h1>
        <p><?= htmlspecialchars($report['description']) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= $exportBase('pdf') ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="<?= $exportBase('excel') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        <a href="<?= $exportBase('csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            <?php if (in_array($type, ['tickets','assets','sla','maintenance'], true)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <input type="text" name="status" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['status']) ?>" placeholder="assigned">
                </div>
            <?php endif; ?>
            <?php if (in_array($type, ['tickets','sla','maintenance'], true)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['critical','high','medium','low'] as $priority): ?>
                            <option value="<?= $priority ?>" <?= $filters['priority'] === $priority ? 'selected' : '' ?>><?= ucfirst($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (in_array($type, ['tickets','assets','maintenance'], true)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($filterOptions['departments'] as $department): ?>
                            <option value="<?= (int)$department->id ?>" <?= (string)$department->id === $filters['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($department->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (in_array($type, ['tickets','assets','inventory'], true)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($categoryOptions as $category): ?>
                            <option value="<?= (int)$category->id ?>" <?= (string)$category->id === $filters['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($category->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (in_array($type, ['tickets','maintenance'], true)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Assignee</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($filterOptions['users'] as $user): ?>
                            <option value="<?= (int)$user->id ?>" <?= (string)$user->id === $filters['assigned_to'] ? 'selected' : '' ?>><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($type === 'maintenance'): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['preventive','corrective','emergency','inspection'] as $taskType): ?>
                            <option value="<?= $taskType ?>" <?= $filters['type'] === $taskType ? 'selected' : '' ?>><?= ucfirst($taskType) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($type === 'user_activity'): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($filterOptions['users'] as $user): ?>
                            <option value="<?= (int)$user->id ?>" <?= (string)$user->id === $filters['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Action</label>
                    <input type="text" name="action" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['action']) ?>" placeholder="login">
                </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="search" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-md-1">
                <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($report['kpis'] as $kpi): ?>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label"><?= htmlspecialchars($kpi['label']) ?></span><div class="kpi-icon <?= htmlspecialchars($kpi['color']) ?>"><i class="bi <?= htmlspecialchars($kpi['icon']) ?>"></i></div></div>
                <div class="kpi-value" style="font-size:1.6rem"><?= htmlspecialchars((string)$kpi['value']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($report['charts'] as $index => $chart): ?>
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header"><?= htmlspecialchars($chart['title']) ?></div>
                <div class="card-body"><div style="height:280px"><canvas id="reportChart<?= $index ?>"></canvas></div></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Report Details</span>
        <span class="badge bg-light text-dark"><?= count($report['rows']) ?> rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <?php foreach ($report['headers'] as $label): ?>
                            <th><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($report['rows'])): ?>
                    <tr><td colspan="<?= count($report['headers']) ?>" class="text-center text-muted py-4">No records matched this filter.</td></tr>
                <?php else: foreach ($report['rows'] as $row): ?>
                    <tr>
                        <?php foreach (array_keys($report['headers']) as $key): ?>
                            <td><small><?= htmlspecialchars((string)($row[$key] ?? '-')) ?></small></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header">Schedule This Report</div>
    <div class="card-body">
        <form action="<?= View::url('reports/schedules/store') ?>" method="POST" class="row g-3 align-items-end">
            <?= CSRF::field() ?>
            <input type="hidden" name="report_type" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="filters_json" value="<?= htmlspecialchars(json_encode($filters)) ?>">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($report['title']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Format</label>
                <select name="format" class="form-select"><option value="pdf">PDF</option><option value="excel">Excel</option><option value="csv">CSV</option></select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Frequency</label>
                <select name="frequency" class="form-select"><option value="daily">Daily</option><option value="weekly" selected>Weekly</option><option value="monthly">Monthly</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Recipients</label>
                <input type="text" name="recipients" class="form-control" placeholder="ops@example.org">
            </div>
            <div class="col-md-2">
                <input type="hidden" name="is_active" value="1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar-plus me-1"></i>Schedule</button>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
const reportCharts = <?= json_encode($report['charts']) ?>;
reportCharts.forEach((chart, index) => {
    const canvas = document.getElementById('reportChart' + index);
    if (!canvas) return;
    new Chart(canvas, {
        type: chart.type,
        data: {
            labels: chart.labels,
            datasets: [{
                data: chart.data,
                label: chart.title,
                backgroundColor: ['#0d6efd', '#20c997', '#f59e0b', '#dc3545', '#6c757d', '#7c3aed', '#0891b2', '#198754'],
                borderWidth: 0,
                borderRadius: chart.type === 'bar' ? 6 : 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: chart.type !== 'bar', position: 'bottom' } },
            scales: chart.type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {}
        }
    });
});
</script>
<?php View::endSection(); ?>
