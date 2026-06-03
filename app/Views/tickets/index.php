<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><h1>Incidents</h1><p>Manage and track all incident tickets.</p></div>
    <a href="<?= View::url('tickets/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Incident</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-3"><select class="form-select form-select-sm" id="filterStatus" onchange="applyFilter()"><option value="">All Statuses</option><option value="new" <?=($filters['status']??'')==='new'?'selected':''?>>New</option><option value="assigned" <?=($filters['status']??'')==='assigned'?'selected':''?>>Assigned</option><option value="in_progress" <?=($filters['status']??'')==='in_progress'?'selected':''?>>In Progress</option><option value="resolved" <?=($filters['status']??'')==='resolved'?'selected':''?>>Resolved</option><option value="closed" <?=($filters['status']??'')==='closed'?'selected':''?>>Closed</option></select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" id="filterPriority" onchange="applyFilter()"><option value="">All Priorities</option><option value="critical" <?=($filters['priority']??'')==='critical'?'selected':''?>>Critical</option><option value="high" <?=($filters['priority']??'')==='high'?'selected':''?>>High</option><option value="medium" <?=($filters['priority']??'')==='medium'?'selected':''?>>Medium</option><option value="low" <?=($filters['priority']??'')==='low'?'selected':''?>>Low</option></select></div>
            <div class="col-md-4"><input type="text" class="form-control form-control-sm" placeholder="Search tickets..." value="<?= htmlspecialchars($filters['search']??'') ?>" id="filterSearch"></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary btn-sm w-100" onclick="applyFilter()"><i class="bi bi-search me-1"></i>Filter</button></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ticket ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Requester</th><th>Assignee</th><th>Created</th><th></th></tr></thead>
                <tbody>
                <?php if(empty($tickets)):?><tr><td colspan="9" class="text-center text-muted py-4">No tickets found</td></tr>
                <?php else: foreach($tickets as $t):?>
                <tr>
                    <td><a href="<?= View::url('tickets/'.$t->id) ?>" class="fw-bold text-primary">#<?= htmlspecialchars($t->ticket_number) ?></a></td>
                    <td><?= htmlspecialchars($t->title) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($t->category_name??'-') ?></small></td>
                    <td><?= View::priorityBadge($t->priority) ?></td>
                    <td><?= View::statusDot($t->status) ?></td>
                    <td><small><?= htmlspecialchars($t->requester_name??'-') ?></small></td>
                    <td><small><?= htmlspecialchars($t->assignee_name??'Unassigned') ?></small></td>
                    <td><small class="text-muted"><?= View::timeAgo($t->created_at) ?></small></td>
                    <td><a href="<?= View::url('tickets/'.$t->id) ?>" class="text-muted"><i class="bi bi-chevron-right"></i></a></td>
                </tr>
                <?php endforeach; endif;?>
                </tbody>
            </table>
        </div>
        <?php if($total > $perPage): $pages = ceil($total/$perPage); ?>
        <nav><ul class="pagination pagination-sm justify-content-end">
            <?php for($i=1;$i<=$pages;$i++):?><li class="page-item <?=$i==$page?'active':''?>"><a class="page-link" href="?page=<?=$i?>&status=<?=$filters['status']??''?>&priority=<?=$filters['priority']??''?>&search=<?=$filters['search']??''?>"><?=$i?></a></li><?php endfor;?>
        </ul></nav>
        <?php endif;?>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
function applyFilter(){
    const s=document.getElementById('filterStatus').value,p=document.getElementById('filterPriority').value,q=document.getElementById('filterSearch').value;
    window.location.href='<?= View::url('tickets') ?>?status='+s+'&priority='+p+'&search='+encodeURIComponent(q);
}
document.getElementById('filterSearch')?.addEventListener('keydown',e=>{if(e.key==='Enter')applyFilter()});
</script>
<?php View::endSection(); ?>
