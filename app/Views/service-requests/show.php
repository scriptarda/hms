<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$userId = Session::userId();

// Check if user is the assigned approver for any pending approval on this request
$isApprover = false;
foreach ($approvals as $appr) {
    if ($appr->approver_id == $userId && $appr->status === 'pending') {
        $isApprover = true;
        break;
    }
}

// Administrators can override and approve
if (in_array($role, ['administrator', 'super_administrator'])) {
    $isApprover = true;
}

View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4 text-muted">Request: <?= htmlspecialchars($request->request_number) ?></span>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $request->type)) ?></span>
            <?= View::priorityBadge($request->priority) ?>
        </div>
        <h1><?= htmlspecialchars($request->title) ?></h1>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('service-requests') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>My Requests</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Request Details & Specs</h5>
                
                <p class="whitespace-pre-wrap small text-primary-dark font-monospace p-3 bg-light rounded border border-light" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($request->description)) ?></p>

                <hr class="my-4 border-light">

                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Submitted By</small>
                        <span class="fw-semibold"><?= htmlspecialchars($request->requester_name) ?></span>
                        <small class="text-muted d-block"><?= htmlspecialchars($request->requester_email) ?></small>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Department Owner</small>
                        <span class="fw-semibold"><?= htmlspecialchars($request->dept_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Approval Sign-off Status</small>
                        <?php
                        $badgeColor = 'secondary';
                        if ($request->status === 'approved') $badgeColor = 'success';
                        elseif ($request->status === 'rejected') $badgeColor = 'danger';
                        elseif ($request->status === 'pending_approval') $badgeColor = 'warning text-dark';
                        ?>
                        <span class="badge bg-<?= $badgeColor ?> fs-6 mt-1"><?= ucwords(str_replace('_', ' ', $request->status)) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Submitted On</small>
                        <span><?= View::date($request->created_at, 'M d, Y H:i') ?></span>
                    </div>
                    <?php if ($request->approved_at): ?>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Authorized On</small>
                            <span class="fw-semibold text-success"><?= View::date($request->approved_at, 'M d, Y H:i') ?></span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Authorized By</small>
                            <span class="fw-semibold text-success"><?= htmlspecialchars($request->approver_name) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approvals Log -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-patch-check me-2 text-primary"></i>Authorization Timeline</h6>
            </div>
            <div class="card-body">
                <?php if (empty($approvals)): ?>
                    <p class="text-muted small text-center mb-0">No approval records logged.</p>
                <?php else: foreach ($approvals as $appr): ?>
                    <div class="p-3 bg-light rounded border mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small text-primary-dark">Approver: <?= htmlspecialchars($appr->approver_name) ?></span>
                            <small class="text-muted"><?= $appr->acted_at ? View::timeAgo($appr->acted_at) : 'Awaiting action' ?></small>
                        </div>
                        <div class="mb-2">
                            <?php
                            $statusBadge = '<span class="badge bg-warning text-dark">Pending Review</span>';
                            if ($appr->status === 'approved') $statusBadge = '<span class="badge bg-success">Approved</span>';
                            elseif ($appr->status === 'rejected') $statusBadge = '<span class="badge bg-danger">Rejected</span>';
                            ?>
                            <small>Decision: <?= $statusBadge ?></small>
                        </div>
                        <?php if ($appr->comments): ?>
                            <p class="mb-0 small text-muted"><strong>Comments:</strong> "<?= htmlspecialchars($appr->comments) ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Approval actions -->
    <div class="col-lg-4">
        <?php if ($request->status === 'pending_approval' && $isApprover): ?>
            <div class="card shadow-sm border-0 mb-4 border-start border-warning border-3">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0 text-warning"><i class="bi bi-shield-check me-2"></i>Sign-off Authorization</h6>
                </div>
                <div class="card-body">
                    <form action="" id="approvalForm" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Sign-off Comments / Rejections reasons</label>
                            <textarea class="form-control form-control-sm" name="comments" rows="3" placeholder="Input any specifications or reasons for decision..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" onclick="submitDecision('approve')" class="btn btn-success btn-sm flex-fill"><i class="bi bi-check2 me-1"></i>Approve & Deploy</button>
                            <button type="submit" onclick="submitDecision('reject')" class="btn btn-danger btn-sm flex-fill"><i class="bi bi-x-lg me-1"></i>Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    function submitDecision(action) {
        const form = document.getElementById('approvalForm');
        if (form) {
            form.action = '<?= View::url("service-requests/" . $request->id) ?>/' + action;
        }
    }
</script>
<?php View::endSection(); ?>
