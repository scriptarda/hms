<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canFulfill = in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator']);

View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4 text-muted">WO #WO-<?= str_pad($task->id, 4, '0', STR_PAD_LEFT) ?></span>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($task->type) ?></span>
            <?= View::statusBadge($task->status) ?>
            <?= View::priorityBadge($task->priority) ?>
        </div>
        <h1><?= htmlspecialchars($task->title) ?></h1>
    </div>
    <div class="page-actions">
        <?php if ($canFulfill && $task->status !== 'completed'): ?>
            <a href="<?= View::url('maintenance/' . $task->id . '/edit') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Parameters</a>
        <?php endif; ?>
        <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Work Orders</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Task Specification & Guidelines</h5>
                <p class="text-primary-dark whitespace-pre-wrap" style="font-size: 0.95rem; line-height: 1.6;"><?= $task->description ? nl2br(htmlspecialchars($task->description)) : '<span class="text-muted italic">No instructions provided.</span>' ?></p>
                
                <hr class="my-4 border-light">
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Linked Asset</small>
                        <?php if ($task->asset_id): ?>
                            <a href="<?= View::url('assets/' . $task->asset_id) ?>" class="fw-semibold text-primary"><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($task->asset_tag) ?> — <?= htmlspecialchars($task->asset_name) ?></a>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Responsible Technician</small>
                        <span class="fw-semibold"><?= htmlspecialchars($task->tech_name ?? 'Queue (Unassigned)') ?></span>
                        <?php if ($task->tech_email): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($task->tech_email) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-4">
                        <small class="text-muted d-block">Scheduled Start Date</small>
                        <span><?= View::date($task->scheduled_date) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Due Date / Deadline</small>
                        <span class="fw-medium text-danger"><?= View::date($task->due_date) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Completed Date</small>
                        <span class="fw-semibold text-success"><?= $task->completed_date ? View::date($task->completed_date) : '-' ?></span>
                    </div>

                    <div class="col-sm-4">
                        <small class="text-muted d-block">Estimated Hours</small>
                        <span><?= $task->estimated_hours ? $task->estimated_hours . ' hrs' : '-' ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Actual Hours Logged</small>
                        <span class="fw-bold"><?= $task->actual_hours ? $task->actual_hours . ' hrs' : '-' ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Work Order Cost</small>
                        <span class="fw-bold text-success"><?= $task->cost ? '$' . number_format($task->cost, 2) : '-' ?></span>
                    </div>
                </div>

                <?php if ($task->status === 'completed' && !empty($task->notes)): ?>
                    <div class="alert alert-success border-0 mt-4 mb-0">
                        <h6 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i>Technician Resolution Report</h6>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($task->notes)) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logs / Audit History -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Maintenance Activity Log</h6>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted small text-center mb-0">No logs found.</p>
                <?php else: foreach ($logs as $log): ?>
                    <div class="p-3 bg-light rounded border mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-primary-dark text-uppercase"><i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($log->action) ?></span>
                            <small class="text-muted"><?= View::timeAgo($log->created_at) ?></small>
                        </div>
                        <p class="mb-1 small text-primary-dark"><?= nl2br(htmlspecialchars($log->notes)) ?></p>
                        <?php if ($log->parts_used): ?>
                            <small class="text-muted d-block mt-1"><strong>Parts Used:</strong> <?= htmlspecialchars($log->parts_used) ?></small>
                        <?php endif; ?>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Logged by: <?= htmlspecialchars($log->user_name) ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Actions -->
    <div class="col-lg-4">
        <?php if ($task->status !== 'completed' && $canFulfill): ?>
            <!-- Complete WO Panel -->
            <div class="card shadow-sm border-0 mb-4 border-start border-success border-3">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0 text-success"><i class="bi bi-check2-square me-2"></i>Close & Complete WO</h6>
                </div>
                <div class="card-body">
                    <form action="<?= View::url('maintenance/' . $task->id . '/complete') ?>" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Actual Hours Worked *</label>
                            <input type="number" step="0.1" class="form-control form-control-sm" name="actual_hours" required placeholder="e.g. 3.5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Parts & Consumables Used</label>
                            <input type="text" class="form-control form-control-sm" name="parts_used" placeholder="e.g. Filters, 2x O2 Sensors">
                            <div class="form-text small">Separate with commas. Optional.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Total Expenses / Cost ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="cost" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fulfillment Notes / Logs *</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="4" required placeholder="Outline calibration results, diagnostic values, or physical repair logs..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Log & Complete Task</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
