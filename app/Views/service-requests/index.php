<?php use App\Helpers\View; use App\Models\ServiceRequest as ServiceRequestModel; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Service Requests</h1>
        <p>Track approvals, fulfillment tickets, and completion status for catalog requests.</p>
    </div>
    <a href="<?= View::url('service-requests/catalog') ?>" class="btn btn-primary"><i class="bi bi-shop-window me-1"></i>Service Catalog</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Total Requests</div><div class="kpi-value"><?= (int)$metrics['total'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Pending Approval</div><div class="kpi-value"><?= (int)$metrics['pending'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">In Fulfillment</div><div class="kpi-value"><?= (int)$metrics['fulfilling'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-label">Completed</div><div class="kpi-value"><?= (int)$metrics['completed'] ?></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="<?= View::url('service-requests') ?>" class="row g-2 mb-4">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All statuses</option>
                    <?php foreach (['pending_approval', 'approved', 'fulfilling', 'completed', 'rejected', 'cancelled'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= ServiceRequestModel::statusLabel($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="type">
                    <option value="">All catalog types</option>
                    <?php foreach ($catalogItems as $item): ?>
                        <option value="<?= htmlspecialchars($item->type) ?>" <?= ($filters['type'] ?? '') === $item->type ? 'selected' : '' ?>><?= htmlspecialchars($item->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search request number, title, requester..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="requestsTable">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Catalog</th>
                        <th>Requester</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No service requests found</td></tr>
                <?php else: foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= View::url('service-requests/' . $r->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($r->request_number) ?></a>
                            <small class="d-block text-muted"><?= htmlspecialchars($r->title) ?></small>
                        </td>
                        <td>
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="status-dot" style="background:<?= htmlspecialchars($r->catalog_color ?? '#64748b') ?>"></span>
                                <span><?= htmlspecialchars($r->catalog_name ?? ucwords(str_replace('_', ' ', $r->type))) ?></span>
                            </span>
                        </td>
                        <td><small><?= htmlspecialchars($r->requester_name ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($r->dept_name ?? '-') ?></small></td>
                        <td><?= View::priorityBadge($r->priority) ?></td>
                        <td><span class="badge bg-<?= ServiceRequestModel::statusColor($r->status) ?>"><?= ServiceRequestModel::statusLabel($r->status) ?></span></td>
                        <td>
                            <?php if ($r->fulfillment_ticket_number): ?>
                                <a href="<?= View::url('tickets/' . $r->ticket_id) ?>" class="small fw-semibold"><?= htmlspecialchars($r->fulfillment_ticket_number) ?></a>
                                <small class="d-block text-muted"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $r->fulfillment_status ?? 'queued'))) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Not queued</small>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= View::date($r->created_at) ?></small></td>
                        <td><a href="<?= View::url('service-requests/' . $r->id) ?>" class="text-muted"><i class="bi bi-chevron-right"></i></a></td>
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
    $('#requestsTable').DataTable({ ordering: true, info: true, paging: true });
});
</script>
<?php View::endSection(); ?>
