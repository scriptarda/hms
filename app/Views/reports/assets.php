<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Asset Metrics Summary</h1>
        <p>Monitor biomedical and IT device health, status distributions, and warranty expirations.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports/export/assets?format=csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
        <a href="<?= View::url('reports/export/assets?format=excel') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports Center</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart: Status Breakdown -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Asset Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Category Breakdown -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">Asset Categories Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 280px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Warranty Warnings Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3">
        <h6 class="fw-bold text-warning mb-0"><i class="bi bi-shield-exclamation me-1"></i>Warranties Expiring Within 90 Days</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Asset Tag</th>
                        <th>Asset Name</th>
                        <th>Warranty Expiry Date</th>
                        <th class="text-end pe-4">Remaining Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiringWarranty)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted small">All asset warranties are currently secure.</td>
                        </tr>
                    <?php else: foreach ($expiringWarranty as $ew): ?>
                        <?php 
                        $days = ceil((strtotime($ew->warranty_expiry) - time()) / 86400);
                        ?>
                        <tr>
                            <td class="ps-4"><a href="<?= View::url('assets/' . $ew->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($ew->asset_tag) ?></a></td>
                            <td><?= htmlspecialchars($ew->name) ?></td>
                            <td class="text-danger fw-semibold"><?= View::date($ew->warranty_expiry) ?></td>
                            <td class="text-end pe-4 fw-bold text-danger"><?= $days ?> days</td>
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
                type: 'pie',
                data: {
                    labels: statusData.map(d => d.status.toUpperCase()),
                    datasets: [{
                        data: statusData.map(d => d.cnt),
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#94a3b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Category Chart
        const catData = <?= json_encode($categoryCounts) ?>;
        const catCtx = document.getElementById('categoryChart');
        if (catCtx) {
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catData.map(d => d.name),
                    datasets: [{
                        data: catData.map(d => d.cnt),
                        backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#94a3b8']
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
