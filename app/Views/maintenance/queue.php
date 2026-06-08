<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Models\MaintenanceTask; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Technician Work Queue</h1>
        <p>Active, unassigned, and overdue work orders ready for maintenance technicians.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= View::url('maintenance/queue') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="scope" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="team" <?= ($filters['scope'] ?? 'team') === 'team' ? 'selected' : '' ?>>My assignments + unassigned</option>
                    <option value="mine" <?= ($filters['scope'] ?? '') === 'mine' ? 'selected' : '' ?>>Assigned to me only</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All priorities</option>
                    <?php foreach (['critical','high','medium','low'] as $priority): ?>
                        <option value="<?= $priority ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>><?= ucwords($priority) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?= View::url('maintenance/create?type=corrective') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>New Corrective WO</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($tasks)): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">No active work is waiting in this queue.</div></div>
        </div>
    <?php else: foreach ($tasks as $task): ?>
        <div class="col-lg-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <a href="<?= View::url('maintenance/' . $task->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars(MaintenanceTask::workOrderLabel($task)) ?></a>
                        <?= View::priorityBadge($task->priority) ?>
                    </div>
                    <h6 class="fw-bold mb-2"><?= htmlspecialchars($task->title) ?></h6>
                    <div class="small text-muted mb-3">
                        <div><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($task->asset_tag ?? 'No linked asset') ?></div>
                        <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($task->tech_name ?: 'Unassigned') ?></div>
                        <div><i class="bi bi-calendar2-event me-1"></i>Due <?= View::date($task->due_date) ?></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <?= View::statusBadge($task->status) ?>
                        <div class="d-flex gap-1">
                            <?php if ($task->status === 'scheduled' || $task->status === 'overdue'): ?>
                                <form action="<?= View::url('maintenance/' . $task->id . '/start') ?>" method="POST">
                                    <?= CSRF::field() ?>
                                    <button class="btn btn-sm btn-success"><i class="bi bi-play-fill"></i></button>
                                </form>
                            <?php endif; ?>
                            <a href="<?= View::url('maintenance/' . $task->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php View::endSection(); ?>
