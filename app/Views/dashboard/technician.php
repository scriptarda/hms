<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-start">
    <div><h1>Technician Overview</h1><p>Queue status for Clinical Systems Node B</p></div>
    <div class="d-flex align-items-center gap-2"><span class="status-dot" style="background:#22c55e"></span><span class="small fw-medium">System Status: Nominal</span></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Assigned To Me</span><div class="kpi-icon blue"><i class="bi bi-person"></i></div></div><div class="kpi-value"><?= $stats['assigned'] ?></div><div class="kpi-change up"><i class="bi bi-arrow-up-right"></i> ~2 from avg</div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Due Today</span><div class="kpi-icon yellow"><i class="bi bi-clock"></i></div></div><div class="kpi-value"><?= str_pad($stats['due_today'],2,'0',STR_PAD_LEFT) ?></div><div class="kpi-change stable">75% CAPACITY</div></div></div>
    <div class="col-md-3"><div class="kpi-card" style="border-color:#dc2626"><div class="kpi-header"><span class="kpi-label" style="color:#dc2626">SLA Warnings</span><div class="kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div></div><div class="kpi-value" style="color:#dc2626"><?= str_pad($stats['sla_warnings'],2,'0',STR_PAD_LEFT) ?></div><a href="<?= View::url('tickets?sla=warning') ?>" class="small" style="color:#dc2626">View Risks</a></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Escalations</span><div class="kpi-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-lightning"></i></div></div><div class="kpi-value"><?= str_pad($stats['escalations'],2,'0',STR_PAD_LEFT) ?></div><div class="kpi-change down">L3 ALERT</div></div></div>
</div>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"><span>My Work Queue</span>
    <div class="d-flex gap-2"><button class="btn btn-outline-secondary btn-sm">Filters</button><a href="<?= View::url('reports/export/csv?type=tickets') ?>" class="btn btn-outline-secondary btn-sm">Export CSV</a></div></div>
    <div class="card-body p-0">
        <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Ticket ID</th><th>Description</th><th>Priority</th><th>Status</th><th>Time Remaining</th><th></th></tr></thead>
            <tbody>
            <?php if(empty($myTickets)):?><tr><td colspan="6" class="text-center text-muted py-4">No tickets assigned</td></tr>
            <?php else: foreach($myTickets as $t): ?>
            <tr>
                <td><a href="<?= View::url('tickets/'.$t->id) ?>" class="fw-bold text-primary">#<?= htmlspecialchars($t->ticket_number) ?></a></td>
                <td><strong><?= htmlspecialchars($t->title) ?></strong><br><small class="text-muted"><?= htmlspecialchars($t->category_name ?? '') ?></small></td>
                <td><?= View::priorityBadge($t->priority) ?></td>
                <td><?= View::statusDot($t->status) ?></td>
                <td><?php
                    if($t->sla_due_at){
                        $remaining = strtotime($t->sla_due_at) - time();
                        if($remaining > 0){
                            $h=floor($remaining/3600);$m=floor(($remaining%3600)/60);
                            $cls = $remaining < 1800 ? 'text-danger fw-bold' : 'text-muted';
                            echo "<span class='{$cls}'><i class='bi bi-clock'></i> {$h}h {$m}m</span>";
                        } else { echo "<span class='text-danger fw-bold'><i class='bi bi-exclamation-triangle'></i> BREACHED</span>"; }
                    } else { echo '-'; }
                ?></td>
                <td><a href="<?= View::url('tickets/'.$t->id) ?>" class="text-muted"><i class="bi bi-chevron-right"></i></a></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-8"><div class="card"><div class="card-header">Active Asset Distribution</div><div class="card-body"><p class="text-muted small">Visualizing active incidents across facility sectors.</p><div style="height:200px"><canvas id="assetDistChart"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header">Shift Performance</div><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><span class="fw-medium">Tickets Resolved</span><span class="fw-bold">06</span></div><div class="progress mb-3" style="height:6px"><div class="progress-bar bg-primary" style="width:60%"></div></div>
        <div class="d-flex justify-content-between mb-2"><span class="fw-medium">Avg Response Time</span><span class="fw-bold">14m 20s</span></div><div class="progress mb-3" style="height:6px"><div class="progress-bar bg-primary" style="width:80%"></div></div>
        <div class="d-flex justify-content-between mb-2"><span class="fw-medium">Compliance Rate</span><span class="fw-bold">98.4%</span></div><div class="progress mb-3" style="height:6px"><div class="progress-bar bg-success" style="width:98%"></div></div>
        <a href="<?= View::url('reports/tickets') ?>" class="btn btn-outline-primary w-100 btn-sm mt-2">View Detailed Report</a>
    </div></div></div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
new Chart(document.getElementById('assetDistChart'),{type:'bar',data:{labels:['Sector A\n(Radiology)','Sector B\n(Intensive Care)','Sector C\n(Outpatient)','Sector D\n(Surgery)','Sector E\n(Lab)'],datasets:[{data:[8,12,5,7,4],backgroundColor:['#1a56db','#0d9488','#dc2626','#7c3aed','#d97706'],borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{display:false},x:{grid:{display:false}}}}});
</script>
<?php View::endSection(); ?>
