<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Notification Inbox</h1>
        <p>Review system alerts, tickets assignments, SLA warnings, and approvals status changes.</p>
    </div>
    <div class="page-actions">
        <?php if (!empty($notifications)): ?>
            <form action="<?= View::url('notifications/read-all') ?>" method="POST" class="d-inline">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope-open me-1"></i>Mark All Read</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 mb-2 d-block"></i>
                <p class="mb-0">Your notification inbox is currently empty.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <?php 
                    // Choose icon and color based on notification type
                    $icon = 'bi-bell';
                    $iconColor = 'bg-primary-light text-primary';
                    if ($n->type === 'ticket_assigned') {
                        $icon = 'bi-person-check';
                        $iconColor = 'bg-primary-light text-primary';
                    } elseif ($n->type === 'sla_warning') {
                        $icon = 'bi-clock-history';
                        $iconColor = 'bg-warning-subtle text-warning';
                    } elseif ($n->type === 'sla_breached') {
                        $icon = 'bi-alarm';
                        $iconColor = 'bg-danger-subtle text-danger';
                    } elseif ($n->type === 'low_stock') {
                        $icon = 'bi-box-seam';
                        $iconColor = 'bg-danger-subtle text-danger';
                    } elseif ($n->type === 'approval_required') {
                        $icon = 'bi-shield-exclamation';
                        $iconColor = 'bg-warning-subtle text-warning';
                    } elseif ($n->type === 'system') {
                        $icon = 'bi-gear';
                        $iconColor = 'bg-secondary-subtle text-secondary';
                    }
                    ?>
                    <div class="list-group-item p-3 border-0 border-bottom d-flex justify-content-between align-items-start gap-3 <?= $n->is_read ? 'bg-transparent' : 'bg-light border-start border-primary border-4' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: <?= str_contains($iconColor, 'bg-primary-light') ? 'var(--primary-light)' : (str_contains($iconColor, 'bg-success') ? '#d1fae5' : (str_contains($iconColor, 'bg-danger') ? '#fee2e2' : '#fef3c7')) ?>; color: <?= str_contains($iconColor, 'text-primary') ? 'var(--primary)' : (str_contains($iconColor, 'text-success') ? 'var(--success)' : (str_contains($iconColor, 'text-danger') ? 'var(--danger)' : 'var(--warning)')) ?>;">
                                <i class="bi <?= $icon ?> fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-primary-dark">
                                    <?php if ($n->link): ?>
                                        <a href="<?= View::url(ltrim($n->link, '/')) ?>" class="text-decoration-none text-primary-dark"><?= htmlspecialchars($n->title) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($n->title) ?>
                                    <?php endif; ?>
                                </h6>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($n->message) ?></p>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= View::timeAgo($n->created_at) ?></small>
                            </div>
                        </div>

                        <?php if (!$n->is_read): ?>
                            <form action="<?= View::url('notifications/' . $n->id . '/read') ?>" method="POST">
                                <?= CSRF::field() ?>
                                <button type="submit" class="btn btn-sm btn-link text-primary text-decoration-none small p-0">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
