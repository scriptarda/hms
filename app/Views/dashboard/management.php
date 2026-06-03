<?php use App\Helpers\View; View::startSection('content'); $s=$stats; ?>
<div class="page-header d-flex justify-content-between align-items-start">
    <div><h1>Executive Analytics Dashboard</h1><p>Real-time health engineering management system overview.</p></div>
    <div class="d-flex gap-2"><a href="<?= View::url('reports/export/csv?type=sla') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a><a href="<?= View::url('reports/export/pdf?type=sla') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-pdf me-1"></i>PDF Export</a></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Overall SLA Compliance %</span><span class="badge bg-success">+2.4%</span></div><div class="kpi-value"><?= $s['sla_compliance'] ?>%</div><div class="progress mt-2" style="height:4px"><div class="progress-bar bg-primary" style="width:<?= $s['sla_compliance'] ?>%"></div></div><small class="text-muted">Target: 95.0%</small></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Average Resolution Time</span><span class="badge bg-success">-14m</span></div><div class="kpi-value"><?php $m=(int)$s['avg_resolution'];echo floor($m/60).'h '.($m%60).'m'; ?></div><small class="text-muted">Vs. 2h 29m last month</small></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Total Active Incidents</span><span class="badge bg-primary"><?= $s['active_incidents'] ?></span></div><div class="kpi-value"><?= number_format($s['total_assets']) ?></div><small class="text-muted">Total registered devices</small></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-8"><div class="card"><div class="card-header">Incident Trends by Month</div><div class="card-body"><div style="height:280px"><canvas id="trendsChart"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header">SLA Status</div><div class="card-body text-center">
        <p class="small text-muted mb-2">Met vs. Breached Analysis</p>
        <div style="height:200px;position:relative"><canvas id="slaDonut"></canvas></div>
        <?php $met=$statusCounts['resolved']??0;$breached=$statusCounts['new']??0;?>
        <div class="d-flex justify-content-between mt-3 px-2"><div><span class="status-dot" style="background:#1a56db"></span>SLA Met<br><strong><?= $met ?></strong></div><div><span class="status-dot" style="background:#dc2626"></span>SLA Breached<br><strong><?= $breached ?></strong></div></div>
    </div></div></div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between"><span>High-Priority Resolution Queue</span><a href="<?= View::url('tickets') ?>" class="text-primary small">View All Tickets</a></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Incident ID</th><th>Department</th><th>Priority</th><th>SLA Status</th><th>Time Logged</th></tr></thead>
        <tbody>
        <?php foreach($activity as $a):?>
        <tr><td><a href="<?= View::url('tickets/'.$a->ticket_id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($a->ticket_number) ?></a></td><td><?= htmlspecialchars($a->first_name.' '.$a->last_name) ?></td><td><span class="badge bg-warning">HIGH</span></td><td><div class="progress" style="width:100px;height:6px"><div class="progress-bar bg-primary" style="width:60%"></div></div><small class="text-muted">2h 14m</small></td><td class="text-muted small"><?= View::date($a->created_at, 'g:i A') ?></td></tr>
        <?php endforeach;?>
        </tbody>
    </table></div></div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
const trendsData = <?= json_encode($trends ?? []) ?>;
const labels = trendsData.map(t => { const d=new Date(t.month+'-01'); return d.toLocaleString('default',{month:'short'}); });
new Chart(document.getElementById('trendsChart'),{type:'bar',data:{labels:labels,datasets:[{label:'Critical/High',data:trendsData.map(t=>t.critical_high),backgroundColor:'#1a56db',borderRadius:4},{label:'Standard',data:trendsData.map(t=>t.total-t.critical_high),backgroundColor:'#94a3b8',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{usePointStyle:true,pointStyle:'circle'}}},scales:{y:{beginAtZero:true,grid:{color:'#f0f2f5'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('slaDonut'),{type:'doughnut',data:{labels:['SLA Met','SLA Breached'],datasets:[{data:[<?= $met ?>,<?= $breached ?>],backgroundColor:['#1a56db','#dc2626'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'75%',plugins:{legend:{display:false}}}});
</script>
<?php View::endSection(); ?>
