<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Incidents Summary Report</h1>
        <p>Analyze support ticket volumes, status breakdowns, priority focus, and department loads.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports/export/tickets?format=csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        <a href="<?= View::url('reports/export/tickets?format=excel') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports Center</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart: Status Breakdown -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Ticket Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Priority Distribution -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Ticket Priority Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3">
        <h6 class="fw-bold mb-0">Incidents Volume by Department</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Department Name</th>
                        <th class="text-center">Active Ticket Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptCounts)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted small">No department ticket records found.</td>
                        </tr>
                    <?php else: foreach ($deptCounts as $dc): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($dc->name) ?></td>
                            <td class="text-center fw-bold text-primary"><?= $dc->cnt ?></td>
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
        // Status Chart
        const statusData = <?= json_encode($statusCounts) ?>;
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: statusData.map(d => d.status.toUpperCase().replace('_', ' ')),
                    datasets: [{
                        label: 'Tickets count',
                        data: statusData.map(d => d.cnt),
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Priority Chart
        const priorityData = <?= json_encode($priorityCounts) ?>;
        const priorityCtx = document.getElementById('priorityChart');
        if (priorityCtx) {
            new Chart(priorityCtx, {
                type: 'pie',
                data: {
                    labels: priorityData.map(d => d.priority.toUpperCase()),
                    datasets: [{
                        data: priorityData.map(d => d.cnt),
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#94a3b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
</script>
<?php View::endSection(); ?>
