<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance Analytics</h1>
        <p>Review scheduled vs completed work orders, task types, and cumulative operational costs.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports/export/maintenance?format=csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        <a href="<?= View::url('reports/export/maintenance?format=excel') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports Center</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart: Status Breakdown -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Work Order Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Table -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Fulfillment Costs by Type</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Maintenance Type</th>
                                <th class="text-center">WO Count</th>
                                <th class="text-end pe-4">Cumulative Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($typeCost)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted small">No maintenance cost logs found.</td>
                                </tr>
                            <?php else: foreach ($typeCost as $tc): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-uppercase"><?= htmlspecialchars($tc->type) ?></td>
                                    <td class="text-center"><?= $tc->cnt ?></td>
                                    <td class="text-end pe-4 fw-bold text-success">$<?= number_format($tc->total_cost ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        const statusData = <?= json_encode($statusCounts) ?>;
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: statusData.map(d => d.status.toUpperCase()),
                    datasets: [{
                        label: 'Tasks',
                        data: statusData.map(d => d.cnt),
                        backgroundColor: '#f59e0b',
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
    });
</script>
<?php View::endSection(); ?>
