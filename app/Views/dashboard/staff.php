<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-start">
    <div><h1>Staff Dashboard</h1><p>Operational overview for HealthCentral HEMS Core.</p></div>
    <div class="btn-group btn-group-sm"><button class="btn btn-outline-primary active">Real-time</button><button class="btn btn-outline-primary">24h History</button></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Open Tickets</span><div class="kpi-icon blue"><i class="bi bi-ticket-detailed"></i></div></div><div class="kpi-value"><?= $stats['open_tickets'] ?></div><div class="kpi-change up"><i class="bi bi-arrow-up-right"></i> +2 since morning</div></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Pending</span><div class="kpi-icon yellow"><i class="bi bi-hourglass-split"></i></div></div><div class="kpi-value"><?= $stats['pending'] ?></div><div class="kpi-change stable">— Stable</div></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Resolved Today</span><div class="kpi-icon green"><i class="bi bi-check-circle"></i></div></div><div class="kpi-value"><?= $stats['resolved_today'] ?></div><div class="kpi-change up"><i class="bi bi-arrow-up-right"></i> 85% of target</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card"><div class="card-header d-flex justify-content-between"><span>Status Overview</span><a href="<?= View::url('tickets') ?>" class="text-primary text-decoration-none small">View Detail</a></div>
        <div class="card-body"><div class="row align-items-center"><div class="col-md-5"><div class="chart-container" style="height:200px"><canvas id="statusChart"></canvas></div></div>
        <div class="col-md-7"><div class="row g-2">
            <?php $total = array_sum($statusCounts ?? []); $sc = $statusCounts ?? []; ?>
            <div class="col-6"><div class="p-2 rounded" style="background:var(--body-bg)"><span class="status-dot" style="background:#1a56db"></span><strong>Resolved</strong><br><span class="fs-5 fw-bold"><?= $sc['resolved'] ?? 0 ?></span> <small class="text-muted">(<?= $total > 0 ? round((($sc['resolved']??0)/$total)*100) : 0 ?>%)</small></div></div>
            <div class="col-6"><div class="p-2 rounded" style="background:var(--body-bg)"><span class="status-dot" style="background:#059669"></span><strong>In Progress</strong><br><span class="fs-5 fw-bold"><?= $sc['in_progress'] ?? 0 ?></span> <small class="text-muted">(<?= $total > 0 ? round((($sc['in_progress']??0)/$total)*100) : 0 ?>%)</small></div></div>
            <div class="col-6"><div class="p-2 rounded" style="background:var(--body-bg)"><span class="status-dot" style="background:#dc2626"></span><strong>New / Urgent</strong><br><span class="fs-5 fw-bold"><?= ($sc['new'] ?? 0) ?></span> <small class="text-muted">(<?= $total > 0 ? round((($sc['new']??0)/$total)*100) : 0 ?>%)</small></div></div>
            <div class="col-6"><div class="p-2 rounded" style="background:var(--body-bg)"><small class="text-muted">Updated 2m ago</small></div></div>
        </div></div></div></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header">Quick Actions</div><div class="card-body d-flex flex-column gap-2">
            <a href="<?= View::url('tickets/create') ?>" class="quick-action"><div class="qa-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-plus-square"></i></div><div><div class="qa-title">Request Equipment</div><div class="qa-desc">Provision clinical hardware</div></div></a>
            <a href="<?= View::url('service-requests/catalog') ?>" class="quick-action"><div class="qa-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-download"></i></div><div><div class="qa-title">Software Install</div><div class="qa-desc">Update or install applications</div></div></a>
            <a href="<?= View::url('service-requests/catalog') ?>" class="quick-action"><div class="qa-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-key"></i></div><div><div class="qa-title">Access Request</div><div class="qa-desc">System permissions & keys</div></div></a>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Recent Activity</div><div class="card-body">
            <?php if(empty($activity)): ?><p class="text-muted text-center py-3">No recent activity</p>
            <?php else: foreach($activity as $a): ?>
            <div class="activity-item">
                <div class="activity-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-pencil"></i></div>
                <div class="activity-content">
                    <div class="title"><strong><?= htmlspecialchars($a->first_name.' '.$a->last_name) ?></strong> <?= htmlspecialchars($a->action) ?> Ticket #<?= htmlspecialchars($a->ticket_number) ?></div>
                    <div class="desc">"<?= htmlspecialchars($a->ticket_title ?? '') ?>"</div>
                    <div class="time"><?= View::timeAgo($a->created_at) ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="maintenance-alert mb-3"><h6><i class="bi bi-wrench me-1"></i>Scheduled Maintenance</h6><p>Server cluster B will be offline for 15 minutes today at 22:00 EST.</p><a href="<?= View::url('maintenance') ?>" class="text-white small"><i class="bi bi-info-circle me-1"></i>Learn More</a></div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
const ctx = document.getElementById('statusChart');
if(ctx){
    new Chart(ctx, {
        type:'doughnut',
        data:{
            labels:['Resolved','In Progress','New','Assigned','Waiting'],
            datasets:[{data:[<?= ($statusCounts['resolved']??0) ?>,<?= ($statusCounts['in_progress']??0) ?>,<?= ($statusCounts['new']??0) ?>,<?= ($statusCounts['assigned']??0) ?>,<?= ($statusCounts['waiting_user']??0)+($statusCounts['waiting_vendor']??0) ?>],
            backgroundColor:['#1a56db','#059669','#dc2626','#0891b2','#64748b'],borderWidth:0}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'70%',plugins:{legend:{display:false},tooltip:{enabled:true}}}
    });
}
</script>
<?php View::endSection(); ?>
