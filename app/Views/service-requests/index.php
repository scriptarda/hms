<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Service Requests Tracker</h1>
        <p>Monitor status, review approvals, and track provisioning workflow of catalog orders.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('service-requests/catalog') ?>" class="btn btn-primary"><i class="bi bi-shop-window me-1"></i>Service Catalog</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="requestsTable">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Requester</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Approval Authorized By</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No service requests found</td>
                        </tr>
                    <?php else: foreach ($requests as $r): ?>
                        <tr>
                            <td><a href="<?= View::url('service-requests/' . $r->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($r->request_number) ?></a></td>
                            <td><small class="text-uppercase fw-semibold"><?= htmlspecialchars(str_replace('_', ' ', $r->type)) ?></small></td>
                            <td><?= htmlspecialchars($r->title) ?></td>
                            <td><small><?= htmlspecialchars($r->requester_name) ?></small></td>
                            <td><small><?= htmlspecialchars($r->dept_name ?? '-') ?></small></td>
                            <td><?= View::priorityBadge($r->priority) ?></td>
                            <td><small><?= htmlspecialchars($r->approver_name ?? 'Pending') ?></small></td>
                            <td>
                                <?php
                                $badgeColor = 'secondary';
                                if ($r->status === 'approved') $badgeColor = 'success';
                                elseif ($r->status === 'rejected') $badgeColor = 'danger';
                                elseif ($r->status === 'pending_approval') $badgeColor = 'warning text-dark';
                                elseif ($r->status === 'completed') $badgeColor = 'dark';
                                ?>
                                <span class="badge bg-<?= $badgeColor ?>"><?= ucwords(str_replace('_', ' ', $r->status)) ?></span>
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
        $('#requestsTable').DataTable({
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
