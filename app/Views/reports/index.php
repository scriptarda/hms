<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header">
    <h1>Reports & Analytics Center</h1>
    <p>Generate, visualize, and export spreadsheets covering service desk performance, clinical engineering assets, and stock valuations.</p>
</div>

<div class="row g-4 mb-4">
    <!-- Card: Ticket metrics -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-exclamation-triangle text-primary fs-3"></i>
                        <h5 class="fw-bold mb-0">Incidents</h5>
                    </div>
                    <p class="text-muted small">Overview of ticket queues, status counts, and priority distributions.</p>
                </div>
                <div class="mt-4">
                    <span class="d-block mb-3 fs-5 fw-bold"><?= $stats['tickets_count'] ?> Total Tickets</span>
                    <a href="<?= View::url('reports/tickets') ?>" class="btn btn-outline-primary btn-sm w-100">Open Report</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Asset Metrics -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-hdd-stack text-success fs-3"></i>
                        <h5 class="fw-bold mb-0">Assets</h5>
                    </div>
                    <p class="text-muted small">Asset category metrics, warranty tracking, and lifecycle status summaries.</p>
                </div>
                <div class="mt-4">
                    <span class="d-block mb-3 fs-5 fw-bold"><?= $stats['assets_count'] ?> Registered Assets</span>
                    <a href="<?= View::url('reports/assets') ?>" class="btn btn-outline-success btn-sm w-100">Open Report</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Maintenance Cost -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-wrench-adjustable text-warning fs-3"></i>
                        <h5 class="fw-bold mb-0">Maintenance</h5>
                    </div>
                    <p class="text-muted small">Preventive scheduling compliance, corrective hours, and operational cost logs.</p>
                </div>
                <div class="mt-4">
                    <span class="d-block mb-3 fs-5 fw-bold">$<?= number_format($stats['maintenance_cost'], 2) ?> Spent</span>
                    <a href="<?= View::url('reports/maintenance') ?>" class="btn btn-outline-warning btn-sm w-100">Open Report</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Inventory Value -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-box-seam text-purple fs-3" style="color: #a855f7;"></i>
                        <h5 class="fw-bold mb-0">Inventory</h5>
                    </div>
                    <p class="text-muted small">Current stock valuation, reorder alert list, and supplier performance.</p>
                </div>
                <div class="mt-4">
                    <span class="d-block mb-3 fs-5 fw-bold">$<?= number_format($stats['inventory_value'], 2) ?> Stock Value</span>
                    <a href="<?= View::url('reports/inventory') ?>" class="btn btn-sm w-100 text-white" style="background:#a855f7;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Open Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SLA Performance Quick Link Card -->
<div class="card shadow-sm border-0 mb-4 bg-light border-start border-primary border-4">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-stopwatch-fill text-primary me-2"></i>Service SLA Compliance Performance</h5>
            <p class="text-muted small mb-0">Monitor response & resolution time targets to ensure hospital operation standards are met.</p>
        </div>
        <a href="<?= View::url('reports/sla') ?>" class="btn btn-primary">Open SLA Dashboard</a>
    </div>
</div>

<!-- Bulk Export Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3">
        <h5 class="card-title fw-bold mb-0"><i class="bi bi-download me-2 text-primary"></i>Bulk Data Export Center</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="p-3 border rounded text-center">
                    <h6 class="fw-bold mb-2">Incidents Database</h6>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?= View::url('reports/export/tickets?format=csv') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                        <a href="<?= View::url('reports/export/tickets?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 border rounded text-center">
                    <h6 class="fw-bold mb-2">Asset Registry</h6>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?= View::url('reports/export/assets?format=csv') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                        <a href="<?= View::url('reports/export/assets?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 border rounded text-center">
                    <h6 class="fw-bold mb-2">Maintenance Tasks</h6>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?= View::url('reports/export/maintenance?format=csv') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                        <a href="<?= View::url('reports/export/maintenance?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-3 border rounded text-center">
                    <h6 class="fw-bold mb-2">Inventory Levels</h6>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?= View::url('reports/export/inventory?format=csv') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                        <a href="<?= View::url('reports/export/inventory?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
