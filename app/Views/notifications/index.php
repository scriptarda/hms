<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content');
$iconFor = static function (string $type): array {
    return match ($type) {
        'ticket_assigned' => ['bi-person-check', 'text-primary', '#dbeafe'],
        'ticket_resolved' => ['bi-check-circle', 'text-success', '#dcfce7'],
        'ticket_escalated', 'sla_breached' => ['bi-alarm', 'text-danger', '#fee2e2'],
        'sla_warning', 'approval_required', 'maintenance_due' => ['bi-exclamation-triangle', 'text-warning', '#fef3c7'],
        'low_stock' => ['bi-box-seam', 'text-danger', '#fee2e2'],
        'report_ready' => ['bi-file-earmark-check', 'text-success', '#dcfce7'],
        'report_failed' => ['bi-file-earmark-x', 'text-danger', '#fee2e2'],
        default => ['bi-bell', 'text-primary', '#dbeafe'],
    };
};
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Notification Center</h1>
        <p>Review in-app alerts, track read state, and manage delivery across email, SMS, push, and realtime updates.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('notifications/preferences') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders me-1"></i>Preferences</a>
        <?php if (!empty($notifications)): ?>
            <form action="<?= View::url('notifications/read-all') ?>" method="POST" class="d-inline">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope-open me-1"></i>Mark All Read</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Total</span><div class="kpi-icon blue"><i class="bi bi-bell"></i></div></div><div class="kpi-value"><?= (int)$stats['total'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Unread</span><div class="kpi-icon yellow"><i class="bi bi-envelope"></i></div></div><div class="kpi-value text-warning"><?= (int)$stats['unread'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Today</span><div class="kpi-icon green"><i class="bi bi-calendar2-check"></i></div></div><div class="kpi-value"><?= (int)$stats['today'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-header"><span class="kpi-label">Attention</span><div class="kpi-icon red"><i class="bi bi-exclamation-octagon"></i></div></div><div class="kpi-value text-danger"><?= (int)$stats['critical'] ?></div></div></div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= View::url('notifications') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">State</label>
                <select name="read" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="unread" <?= ($filters['read'] ?? '') === 'unread' ? 'selected' : '' ?>>Unread</option>
                    <option value="read" <?= ($filters['read'] ?? '') === 'read' ? 'selected' : '' ?>>Read</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <?php foreach ($types as $type => $label): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Severity</label>
                <select name="severity" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['info','success','warning','danger'] as $severity): ?>
                        <option value="<?= $severity ?>" <?= ($filters['severity'] ?? '') === $severity ? 'selected' : '' ?>><?= ucfirst($severity) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="search" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Title or message">
            </div>
            <div class="col-md-1">
                <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 mb-2 d-block"></i>
                <p class="mb-0">No notifications match this view.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush" id="notificationList">
                <?php foreach ($notifications as $n): ?>
                    <?php [$icon, $iconColor, $iconBg] = $iconFor($n->type); ?>
                    <div class="list-group-item p-3 border-0 border-bottom d-flex justify-content-between align-items-start gap-3 <?= $n->is_read ? 'bg-transparent' : 'bg-light border-start border-primary border-4' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:<?= $iconBg ?>;">
                                <i class="bi <?= $icon ?> <?= $iconColor ?> fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0 text-primary-dark">
                                        <?php if ($n->link): ?>
                                            <a href="<?= View::url(ltrim($n->link, '/')) ?>" class="text-decoration-none text-primary-dark"><?= htmlspecialchars($n->title) ?></a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($n->title) ?>
                                        <?php endif; ?>
                                    </h6>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($types[$n->type] ?? ucwords(str_replace('_', ' ', $n->type))) ?></span>
                                    <?= View::statusBadge($n->severity ?? 'info') ?>
                                </div>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($n->message) ?></p>
                                <small class="text-muted"><?= View::timeAgo($n->created_at) ?><?= $n->read_at ? ' · Read ' . View::timeAgo($n->read_at) : '' ?></small>
                            </div>
                        </div>

                        <div class="text-end">
                            <?php if (!$n->is_read): ?>
                                <form action="<?= View::url('notifications/' . $n->id . '/read') ?>" method="POST">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-primary text-decoration-none small p-0">Mark read</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= View::url('notifications/' . $n->id . '/unread') ?>" method="POST">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-muted text-decoration-none small p-0">Mark unread</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
