<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>SLA Performance Dashboard</h1>
        <p>Analyze response & resolution targets, compliance rates, and breach occurrences.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('reports') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports Center</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- SLA compliance widget -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 bg-light border-start border-primary border-4">
            <div class="card-body p-4 text-center">
                <small class="text-muted text-uppercase fw-bold d-block mb-1">SLA Compliance Rate (Last 30 days)</small>
                <span class="fs-1 fw-extrabold text-primary" style="font-weight: 800; font-size: 3.5rem !important;"><?= $compliance ?>%</span>
                <p class="text-muted small mt-2 mb-0">Target threshold is 95% compliance</p>
            </div>
        </div>
    </div>

    <!-- Avg resolution widget -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 bg-light border-start border-success border-4">
            <div class="card-body p-4 text-center">
                <small class="text-muted text-uppercase fw-bold d-block mb-1">Avg Ticket Resolution Time</small>
                <span class="fs-1 fw-extrabold text-success" style="font-weight: 800; font-size: 3.5rem !important;"><?= $avg_resolution ?> hrs</span>
                <p class="text-muted small mt-2 mb-0">Includes both IT hardware and biomedical assets</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Chart: SLA statuses -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0">SLA Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="slaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        const slaData = <?= json_encode($slaStatus) ?>;
        const slaCtx = document.getElementById('slaChart');
        if (slaCtx) {
            new Chart(slaCtx, {
                type: 'bar',
                data: {
                    labels: slaData.map(d => d.sla_status.toUpperCase().replace('_', ' ')),
                    datasets: [{
                        label: 'Tickets count',
                        data: slaData.map(d => d.cnt),
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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
