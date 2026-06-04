<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session; use App\Models\ServiceRequest as ServiceRequestModel;
$role = Session::get('role', 'staff');
$userId = Session::userId();
$isApprover = false;
foreach ($approvals as $appr) {
    if ((int)$appr->approver_id === $userId && $appr->status === 'pending') {
        $isApprover = true;
        break;
    }
}
$isApprover = $isApprover || in_array($role, ['administrator', 'super_administrator'], true) || in_array('service_requests.approve', Session::get('permissions', []), true);
$canFulfill = in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator'], true);
$canCancel = ((int)$request->requester_id === $userId || in_array($role, ['administrator', 'super_administrator'], true)) && !in_array($request->status, ['completed', 'rejected', 'cancelled'], true);
$steps = ServiceRequestModel::workflowSteps($request->status);
View::startSection('styles'); ?>
<style>
.workflow-step { display:flex; align-items:center; gap:.6rem; padding:.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--card-bg); }
.workflow-step .step-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:var(--body-bg); color:var(--text-muted); }
.workflow-step.done .step-icon, .workflow-step.current .step-icon { background:var(--primary); color:#fff; }
.workflow-step.current { border-color:var(--primary); }
</style>
<?php View::endSection(); View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="fs-5 text-muted"><?= htmlspecialchars($request->request_number) ?></span>
            <span class="badge bg-<?= ServiceRequestModel::statusColor($request->status) ?>"><?= ServiceRequestModel::statusLabel($request->status) ?></span>
            <?= View::priorityBadge($request->priority) ?>
        </div>
        <h1><?= htmlspecialchars($request->title) ?></h1>
        <p><?= htmlspecialchars($request->catalog_name ?? ucwords(str_replace('_', ' ', $request->type))) ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('service-requests') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Requests</a>
        <?php if ($request->ticket_id): ?>
            <a href="<?= View::url('tickets/' . $request->ticket_id) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-ticket-detailed me-1"></i><?= htmlspecialchars($request->fulfillment_ticket_number) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>Workflow</h5>
                <div class="row g-2">
                    <?php foreach ($steps as $step): ?>
                        <div class="col-md">
                            <div class="workflow-step <?= htmlspecialchars($step['state']) ?>">
                                <div class="step-icon"><i class="bi <?= htmlspecialchars($step['icon']) ?>"></i></div>
                                <div class="small fw-semibold"><?= htmlspecialchars($step['label']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Request Details</h5>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6"><small class="text-muted d-block">Submitted By</small><span class="fw-semibold"><?= htmlspecialchars($request->requester_name) ?></span><small class="text-muted d-block"><?= htmlspecialchars($request->requester_email) ?></small></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Department</small><span class="fw-semibold"><?= htmlspecialchars($request->dept_name ?? '-') ?></span></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Created</small><span><?= View::date($request->created_at, 'M d, Y H:i') ?></span></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Approval</small><span><?= $request->approved_at ? 'Approved by ' . htmlspecialchars($request->approver_name ?? '-') : 'Awaiting sign-off' ?></span></div>
                </div>

                <?php if ($request->description): ?>
                    <div class="p-3 bg-light rounded border small mb-3"><?= nl2br(htmlspecialchars($request->description)) ?></div>
                <?php endif; ?>

                <div class="row g-3">
                    <?php foreach ($fieldValues as $field): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-muted d-block"><?= htmlspecialchars($field->field_label) ?></small>
                                <span class="fw-semibold"><?= htmlspecialchars($field->field_value ?: '-') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-patch-check me-2 text-primary"></i>Approvals</h6>
            </div>
            <div class="card-body">
                <?php if (empty($approvals)): ?>
                    <p class="text-muted small mb-0">No approval steps were required.</p>
                <?php else: foreach ($approvals as $appr): ?>
                    <div class="p-3 bg-light rounded border mb-2">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <strong><?= htmlspecialchars($appr->approver_name) ?></strong>
                            <?php
                            $approvalColor = $appr->status === 'approved' ? 'success' : ($appr->status === 'rejected' ? 'danger' : 'warning text-dark');
                            ?>
                            <span class="badge bg-<?= $approvalColor ?>"><?= ucwords($appr->status) ?></span>
                        </div>
                        <small class="text-muted"><?= $appr->acted_at ? View::date($appr->acted_at, 'M d, Y H:i') : 'Awaiting action' ?></small>
                        <?php if ($appr->comments): ?><p class="small mb-0 mt-2"><?= htmlspecialchars($appr->comments) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Tracking Timeline</h6>
            </div>
            <div class="card-body">
                <?php if (empty($activity)): ?>
                    <p class="text-muted small mb-0">No activity recorded yet.</p>
                <?php else: foreach ($activity as $event): ?>
                    <div class="activity-item">
                        <div class="activity-icon bg-primary text-white"><i class="bi bi-dot"></i></div>
                        <div class="activity-content">
                            <div class="title"><?= htmlspecialchars($event->title) ?></div>
                            <?php if ($event->description): ?><div class="desc"><?= htmlspecialchars($event->description) ?></div><?php endif; ?>
                            <div class="time"><?= View::date($event->created_at, 'M d, Y H:i') ?><?= $event->user_name ? ' by ' . htmlspecialchars($event->user_name) : '' ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($request->status === 'pending_approval' && $isApprover): ?>
            <div class="card mb-4 border-start border-warning border-3">
                <div class="card-header bg-transparent py-3"><h6 class="fw-bold mb-0 text-warning"><i class="bi bi-shield-check me-2"></i>Approval Action</h6></div>
                <div class="card-body">
                    <form action="" id="approvalForm" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Decision Comments</label>
                            <textarea class="form-control form-control-sm" name="comments" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" onclick="submitDecision('approve')" class="btn btn-success btn-sm flex-fill"><i class="bi bi-check2 me-1"></i>Approve</button>
                            <button type="submit" onclick="submitDecision('reject')" class="btn btn-danger btn-sm flex-fill"><i class="bi bi-x-lg me-1"></i>Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-transparent py-3"><h6 class="fw-bold mb-0"><i class="bi bi-tools me-2 text-primary"></i>Fulfillment</h6></div>
            <div class="card-body">
                <?php if ($request->ticket_id): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Ticket</small>
                        <a class="fw-bold" href="<?= View::url('tickets/' . $request->ticket_id) ?>"><?= htmlspecialchars($request->fulfillment_ticket_number) ?></a>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $request->fulfillment_status ?? 'queued'))) ?></span>
                    </div>
                    <?php if ($request->fulfillment_assignee_name): ?>
                        <div class="mb-3"><small class="text-muted d-block">Owner</small><span class="fw-semibold"><?= htmlspecialchars($request->fulfillment_assignee_name) ?></span></div>
                    <?php endif; ?>
                    <?php if ($canFulfill && in_array($request->status, ['approved', 'fulfilling'], true)): ?>
                        <?php if ($request->fulfillment_status !== 'in_progress'): ?>
                            <form action="<?= View::url('service-requests/' . $request->id . '/fulfillment/start') ?>" method="POST" class="mb-2">
                                <?= CSRF::field() ?>
                                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-play-circle me-1"></i>Start Fulfillment</button>
                            </form>
                        <?php endif; ?>
                        <form action="<?= View::url('service-requests/' . $request->id . '/fulfillment/complete') ?>" method="POST">
                            <?= CSRF::field() ?>
                            <textarea class="form-control form-control-sm mb-2" name="completion_notes" rows="3" placeholder="Completion notes"></textarea>
                            <button class="btn btn-success btn-sm w-100"><i class="bi bi-check2-circle me-1"></i>Mark Completed</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Fulfillment will be queued after approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canCancel): ?>
            <div class="card border-start border-secondary border-3">
                <div class="card-header bg-transparent py-3"><h6 class="fw-bold mb-0"><i class="bi bi-slash-circle me-2"></i>Cancel Request</h6></div>
                <div class="card-body">
                    <form action="<?= View::url('service-requests/' . $request->id . '/cancel') ?>" method="POST" data-confirm="Cancel this service request?">
                        <?= CSRF::field() ?>
                        <textarea class="form-control form-control-sm mb-2" name="cancel_reason" rows="2" placeholder="Reason"></textarea>
                        <button class="btn btn-outline-secondary btn-sm w-100">Cancel Request</button>
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
    if (form) form.action = '<?= View::url("service-requests/" . $request->id) ?>/' + action;
}
</script>
<?php View::endSection(); ?>
