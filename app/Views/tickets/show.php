<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$userId = Session::userId();
$canFulfill = in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator']);
$isAssignee = $ticket->assigned_to == $userId;
$isRequester = $ticket->requester_id == $userId;

View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4 text-muted">#<?= htmlspecialchars($ticket->ticket_number) ?></span>
            <?= View::priorityBadge($ticket->priority) ?>
            <?= View::statusBadge($ticket->status) ?>
            <?php if ($ticket->sla_status === 'warning'): ?>
                <span class="badge bg-warning text-white">SLA Warning</span>
            <?php elseif ($ticket->sla_status === 'breached'): ?>
                <span class="badge bg-danger">SLA Breached</span>
            <?php endif; ?>
        </div>
        <h1><?= htmlspecialchars($ticket->title) ?></h1>
    </div>
    <div class="page-actions">
        <?php if ($canFulfill || $isRequester): ?>
            <a href="<?= View::url('tickets/' . $ticket->id . '/edit') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Ticket</a>
        <?php endif; ?>
        <a href="<?= View::url('tickets') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details & Comments -->
    <div class="col-lg-8">
        <!-- Details Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Description</h5>
                <p class="text-primary-dark whitespace-pre-wrap" style="font-size: 0.95rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($ticket->description)) ?></p>
                
                <hr class="my-4 border-light">
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Requester</small>
                        <span class="fw-semibold"><?= htmlspecialchars($ticket->requester_name) ?></span> 
                        <small class="text-muted">(<?= htmlspecialchars($ticket->requester_email) ?>)</small>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Assignee</small>
                        <span class="fw-semibold"><?= htmlspecialchars($ticket->assignee_name ?? 'Unassigned') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Category</small>
                        <span><?= htmlspecialchars($ticket->category_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Subcategory</small>
                        <span><?= htmlspecialchars($ticket->subcategory_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Linked Asset</small>
                        <?php if(!empty($ticket->asset_id)): ?>
                            <a href="<?= View::url('assets/' . $ticket->asset_id) ?>" class="fw-semibold text-primary"><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($ticket->asset_tag) ?></a>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Department</small>
                        <span><?= htmlspecialchars($ticket->dept_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Building / Room</small>
                        <span><?= htmlspecialchars(($ticket->building_name ?? '-') . ($ticket->room_id ? ' / Room '.$ticket->room_id : '')) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">SLA Target Due</small>
                        <span class="text-danger fw-semibold"><?= View::date($ticket->sla_due_at, 'M d, Y H:i') ?></span>
                    </div>
                </div>

                <?php if (!empty($ticket->resolution_notes)): ?>
                    <div class="alert alert-success border-0 mt-4 mb-0">
                        <h6 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i>Resolution Notes</h6>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($ticket->resolution_notes)) ?></p>
                        <?php if ($ticket->resolved_at): ?>
                            <small class="text-muted d-block mt-2">Resolved: <?= View::date($ticket->resolved_at, 'M d, Y H:i') ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attachments Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-paperclip me-2 text-primary"></i>Attachments</h6>
            </div>
            <div class="card-body">
                <?php if (empty($attachments)): ?>
                    <p class="text-muted small mb-0">No attachments uploaded.</p>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($attachments as $att): ?>
                            <div class="col-md-6">
                                <div class="p-2 border rounded d-flex align-items-center justify-content-between bg-light">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
                                        <div class="overflow-hidden">
                                            <span class="d-block text-truncate fw-medium small mb-0"><?= htmlspecialchars($att->file_name) ?></span>
                                            <small class="text-muted" style="font-size:0.75rem;"><?= round($att->file_size / 1024) ?> KB</small>
                                        </div>
                                    </div>
                                    <a href="<?= View::url('uploads/tickets/' . $att->file_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-chat-left-text me-2 text-primary"></i>Comments Feed</h6>
            </div>
            <div class="card-body">
                <!-- Add Comment Form -->
                <form action="<?= View::url('tickets/' . $ticket->id . '/comment') ?>" method="POST" class="mb-4">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="3" placeholder="Type a message..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <?php if ($canFulfill): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="isInternal">
                                <label class="form-check-label small fw-medium text-warning" for="isInternal">
                                    <i class="bi bi-shield-lock-fill me-1"></i>Internal Comment (Staff won't see)
                                </label>
                            </div>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-chat me-1"></i>Add Comment</button>
                    </div>
                </form>

                <!-- List Comments -->
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="text-center text-muted py-3 small">No comments yet.</p>
                    <?php else: foreach ($comments as $com): ?>
                        <?php 
                        // Skip internal comments for staff
                        if ($com->is_internal && !$canFulfill) continue; 
                        ?>
                        <div class="card border-0 mb-3 <?= $com->is_internal ? 'bg-warning-subtle border-start border-warning border-3' : 'bg-light' ?>">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            <?= htmlspecialchars(strtoupper(substr($com->user_name, 0, 1))) ?>
                                        </div>
                                        <span class="fw-semibold small"><?= htmlspecialchars($com->user_name) ?></span>
                                        <?php if ($com->is_internal): ?>
                                            <span class="badge bg-warning text-dark" style="font-size:0.6rem;"><i class="bi bi-shield-lock me-1"></i>Internal</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= View::timeAgo($com->created_at) ?></small>
                                </div>
                                <p class="mb-0 small text-primary-dark whitespace-pre-wrap"><?= nl2br(htmlspecialchars($com->comment)) ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Fulfillment Panel & History -->
    <div class="col-lg-4">
        <!-- Actions Card -->
        <?php if ($canFulfill || $isRequester): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Fulfillment Actions</h6>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <!-- Status specific controls -->
                <?php if ($canFulfill): ?>
                    <!-- Assignment -->
                    <form action="<?= View::url('tickets/' . $ticket->id . '/assign') ?>" method="POST" class="p-2 bg-light rounded border">
                        <?= CSRF::field() ?>
                        <label class="form-label small fw-bold">Assign Ticket</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" name="assigned_to" required>
                                <option value="">Select Technician...</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech->id ?>" <?= $ticket->assigned_to == $tech->id ? 'selected' : '' ?>><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                        </div>
                    </form>

                    <!-- Escalate -->
                    <?php if ($ticket->status !== 'resolved' && $ticket->status !== 'closed'): ?>
                        <form action="<?= View::url('tickets/' . $ticket->id . '/escalate') ?>" method="POST" data-confirm="Escalate this ticket? This increases priority level.">
                            <?= CSRF::field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-graph-up-arrow me-1"></i>Escalate SLA & Priority</button>
                        </form>
                    <?php endif; ?>

                    <!-- Resolve -->
                    <?php if ($ticket->status !== 'resolved' && $ticket->status !== 'closed'): ?>
                        <button class="btn btn-success btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#resolveCollapse"><i class="bi bi-check2-square me-1"></i>Mark Resolved</button>
                        <div class="collapse mt-2" id="resolveCollapse">
                            <form action="<?= View::url('tickets/' . $ticket->id . '/resolve') ?>" method="POST" class="p-3 border rounded bg-light">
                                <?= CSRF::field() ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Resolution Notes</label>
                                    <textarea class="form-control form-control-sm" name="resolution_notes" rows="3" placeholder="Explain how this issue was resolved..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100">Submit Resolution</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Close -->
                <?php if ($ticket->status === 'resolved' && ($canFulfill || $isRequester)): ?>
                    <form action="<?= View::url('tickets/' . $ticket->id . '/close') ?>" method="POST" data-confirm="Close this ticket?">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-dark btn-sm w-100"><i class="bi bi-x-circle me-1"></i>Close Ticket (Permanent)</button>
                    </form>
                <?php endif; ?>

                <!-- Reopen -->
                <?php if (($ticket->status === 'resolved' || $ticket->status === 'closed') && ($canFulfill || $isRequester)): ?>
                    <form action="<?= View::url('tickets/' . $ticket->id . '/reopen') ?>" method="POST" data-confirm="Reopen this ticket?">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-warning text-white btn-sm w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reopen Ticket</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- History Timeline -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Audit Timeline</h6>
            </div>
            <div class="card-body p-0" style="max-height: 380px; overflow-y: auto;">
                <div class="p-3">
                    <?php if (empty($history)): ?>
                        <p class="text-muted small text-center my-3">No activity logs recorded.</p>
                    <?php else: foreach ($history as $hist): ?>
                        <div class="d-flex gap-2 mb-3 align-items-start">
                            <div class="timeline-dot bg-primary mt-1" style="width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;"></div>
                            <div>
                                <span class="d-block small fw-bold"><?= htmlspecialchars($hist->user_name) ?></span>
                                <small class="text-muted d-block" style="font-size:0.75rem;">
                                    <?= htmlspecialchars($hist->action) ?>
                                    <?php if ($hist->field_name): ?>
                                        (<?= htmlspecialchars($hist->field_name) ?>)
                                    <?php endif; ?>
                                </small>
                                <small class="text-muted d-block" style="font-size:0.7rem;"><?= View::timeAgo($hist->created_at) ?></small>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
