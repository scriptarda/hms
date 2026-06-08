<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session; use App\Models\MaintenanceTask;
$role = Session::get('role', 'staff');
$canFulfill = in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator'], true);
$isClosed = in_array($task->status, ['completed', 'cancelled'], true);
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="fs-5 text-muted"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></span>
            <span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($task->type) ?></span>
            <?= View::statusBadge($task->status) ?>
            <?= View::priorityBadge($task->priority) ?>
        </div>
        <h1><?= htmlspecialchars($task->title) ?></h1>
        <p><?= htmlspecialchars($task->asset_tag ? ($task->asset_tag . ' - ' . $task->asset_name) : 'General facility maintenance') ?></p>
    </div>
    <div class="page-actions">
        <?php if (!$isClosed): ?>
            <a href="<?= View::url('maintenance/' . $task->id . '/edit') ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">Work Order Specification</div>
            <div class="card-body">
                <p class="mb-4"><?= $task->description ? nl2br(htmlspecialchars($task->description)) : '<span class="text-muted">No description provided.</span>' ?></p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Asset</small>
                        <?php if ($task->asset_id): ?>
                            <a href="<?= View::url('assets/' . $task->asset_id) ?>" class="fw-semibold"><?= htmlspecialchars($task->asset_tag) ?> - <?= htmlspecialchars($task->asset_name) ?></a>
                        <?php else: ?>
                            <span class="text-muted">No linked asset</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-semibold"><?= htmlspecialchars($task->dept_name ?: '-') ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Scheduled</small>
                        <span><?= View::date($task->scheduled_date) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Due</small>
                        <span class="<?= strtotime($task->due_date) < strtotime(date('Y-m-d')) && !$isClosed ? 'text-danger fw-bold' : '' ?>"><?= View::date($task->due_date) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Completed</small>
                        <span><?= $task->completed_date ? View::date($task->completed_date) : '-' ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Technician</small>
                        <span><?= htmlspecialchars($task->tech_name ?: 'Unassigned') ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Estimated / Actual</small>
                        <span><?= $task->estimated_hours ? number_format((float)$task->estimated_hours, 1) . 'h' : '-' ?> / <?= $task->actual_hours ? number_format((float)$task->actual_hours, 1) . 'h' : '-' ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Cost</small>
                        <span class="fw-semibold text-success">$<?= number_format((float)($task->cost ?? 0), 2) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Failure Code</small>
                        <span><?= htmlspecialchars($task->failure_code ?: '-') ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Downtime</small>
                        <span><?= (int)($task->downtime_minutes ?? 0) ?> min</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Recurring PM</small>
                        <span><?= $task->schedule_frequency ? htmlspecialchars(MaintenanceTask::frequencyLabel($task->schedule_frequency)) : '-' ?></span>
                    </div>
                </div>
                <?php if (!empty($task->notes)): ?>
                    <div class="alert alert-light border mt-4 mb-0">
                        <strong>Notes:</strong> <?= nl2br(htmlspecialchars($task->notes)) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">Checklist</div>
            <div class="card-body">
                <?php if (empty($checklist)): ?>
                    <p class="text-muted small mb-0">No checklist captured for this work order.</p>
                <?php else: ?>
                    <?php foreach ($checklist as $item): ?>
                        <div class="d-flex align-items-center gap-2 border-bottom py-2">
                            <i class="bi bi-square text-muted"></i>
                            <span><?= htmlspecialchars($item['label'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header">Service Activity Log</div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted small text-center mb-0">No activity has been logged yet.</p>
                <?php else: foreach ($logs as $log): ?>
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-bold text-uppercase small"><?= htmlspecialchars($log->action) ?></span>
                            <small class="text-muted"><?= View::timeAgo($log->created_at) ?></small>
                        </div>
                        <p class="small mb-1"><?= nl2br(htmlspecialchars($log->notes ?? '')) ?></p>
                        <?php if ($log->parts_used): ?><small class="text-muted d-block"><strong>Parts:</strong> <?= htmlspecialchars($log->parts_used) ?></small><?php endif; ?>
                        <?php if ($log->labor_hours): ?><small class="text-muted d-block"><strong>Labor:</strong> <?= number_format((float)$log->labor_hours, 1) ?>h</small><?php endif; ?>
                        <small class="text-muted">Logged by <?= htmlspecialchars($log->user_name) ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($canFulfill && !$isClosed): ?>
            <?php if ($task->status !== 'in_progress'): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header text-success">Start Work</div>
                    <div class="card-body">
                        <form action="<?= View::url('maintenance/' . $task->id . '/start') ?>" method="POST">
                            <?= CSRF::field() ?>
                            <textarea class="form-control form-control-sm mb-3" name="notes" rows="2" placeholder="Optional start note"></textarea>
                            <button class="btn btn-success w-100"><i class="bi bi-play-fill me-1"></i>Start Work Order</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4 border-start border-success border-3">
                <div class="card-header text-success">Complete Work Order</div>
                <div class="card-body">
                    <form action="<?= View::url('maintenance/' . $task->id . '/complete') ?>" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Actual Hours *</label>
                            <input type="number" step="0.1" class="form-control form-control-sm" name="actual_hours" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Parts Used</label>
                            <input type="text" class="form-control form-control-sm" name="parts_used">
                        </div>
                        <div class="row g-2">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Cost</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="cost">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Downtime</label>
                                <input type="number" class="form-control form-control-sm" name="downtime_minutes" value="<?= (int)($task->downtime_minutes ?? 0) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Failure Code</label>
                            <input type="text" class="form-control form-control-sm" name="failure_code" value="<?= htmlspecialchars($task->failure_code ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Completion Notes *</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="4" required></textarea>
                        </div>
                        <button class="btn btn-success w-100"><i class="bi bi-check2-square me-1"></i>Log & Complete</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header text-danger">Cancel Work Order</div>
                <div class="card-body">
                    <form action="<?= View::url('maintenance/' . $task->id . '/cancel') ?>" method="POST">
                        <?= CSRF::field() ?>
                        <textarea class="form-control form-control-sm mb-3" name="reason" rows="2" placeholder="Cancellation reason"></textarea>
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
