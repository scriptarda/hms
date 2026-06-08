<?php
use App\Helpers\View;
use App\Helpers\CSRF;

$formatMinutes = static function ($minutes): string {
    $minutes = (int)$minutes;
    if ($minutes >= 1440 && $minutes % 1440 === 0) {
        return ($minutes / 1440) . 'd';
    }
    if ($minutes >= 60 && $minutes % 60 === 0) {
        return ($minutes / 60) . 'h';
    }
    return $minutes . 'm';
};

View::startSection('content');
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>SLA Management</h1>
        <p>Monitor response and resolution commitments, escalation state, and ticket risk.</p>
    </div>
    <div class="page-actions">
        <form action="<?= View::url('sla/monitor/run') ?>" method="POST" class="d-inline">
            <?= CSRF::field() ?>
            <input type="hidden" name="limit" value="500">
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-clockwise me-1"></i>Run Monitor</button>
        </form>
        <a href="<?= View::url('reports/sla') ?>" class="btn btn-outline-secondary"><i class="bi bi-bar-chart me-1"></i>Report</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Open</span><div class="kpi-icon blue"><i class="bi bi-ticket-detailed"></i></div></div>
            <div class="kpi-value"><?= (int)$metrics['open_tickets'] ?></div>
            <div class="kpi-change stable">Tickets under SLA</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Response Warn</span><div class="kpi-icon yellow"><i class="bi bi-hourglass-top"></i></div></div>
            <div class="kpi-value text-warning"><?= (int)$metrics['response_warning'] ?></div>
            <div class="kpi-change stable">Near response target</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Response Breach</span><div class="kpi-icon red"><i class="bi bi-exclamation-octagon"></i></div></div>
            <div class="kpi-value text-danger"><?= (int)$metrics['response_breached'] ?></div>
            <div class="kpi-change stable">Missed first response</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Resolution Warn</span><div class="kpi-icon yellow"><i class="bi bi-stopwatch"></i></div></div>
            <div class="kpi-value text-warning"><?= (int)$metrics['resolution_warning'] ?></div>
            <div class="kpi-change stable"><?= (int)$metrics['due_soon'] ?> due soon</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Resolution Breach</span><div class="kpi-icon red"><i class="bi bi-alarm"></i></div></div>
            <div class="kpi-value text-danger"><?= (int)$metrics['resolution_breached'] ?></div>
            <div class="kpi-change stable"><?= (int)$metrics['escalated'] ?> escalated</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Compliance</span><div class="kpi-icon green"><i class="bi bi-shield-check"></i></div></div>
            <div class="kpi-value" style="font-size:1.6rem"><?= number_format((float)$metrics['resolution_compliance'], 1) ?>%</div>
            <div class="kpi-change stable"><?= (int)$metrics['resolved_30d'] ?> resolved in 30d</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>SLA Rules</span>
                <span class="badge bg-light text-dark"><?= count($rules) ?> configured</span>
            </div>
            <div class="card-body">
                <?php if (empty($rules)): ?>
                    <p class="text-muted small text-center mb-0">No SLA rules configured.</p>
                <?php else: foreach ($rules as $rule): ?>
                    <form action="<?= View::url('sla/rules/' . $rule->id . '/update') ?>" method="POST" class="border-bottom pb-3 mb-3">
                        <?= CSRF::field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Rule</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($rule->name) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Priority</label>
                                <select name="priority" class="form-select form-select-sm">
                                    <?php foreach (['critical','high','medium','low'] as $priority): ?>
                                        <option value="<?= $priority ?>" <?= $rule->priority === $priority ? 'selected' : '' ?>><?= ucfirst($priority) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Response</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="1" name="response_time" class="form-control" value="<?= (int)$rule->response_time ?>" required>
                                    <span class="input-group-text">min</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Resolution</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="1" name="resolution_time" class="form-control" value="<?= (int)$rule->resolution_time ?>" required>
                                    <span class="input-group-text">min</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Escalate</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0" name="escalation_time" class="form-control" value="<?= (int)$rule->escalation_time ?>">
                                    <span class="input-group-text">min</span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-save"></i></button>
                            </div>
                        </div>
                        <div class="row g-2 align-items-end mt-1">
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Warning %</label>
                                <input type="number" min="1" max="99" name="warning_threshold" class="form-control form-control-sm" value="<?= (int)$rule->warning_threshold ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Notify Roles</label>
                                <input type="text" name="notify_roles" class="form-control form-control-sm" value="<?= htmlspecialchars($rule->notify_roles) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Escalation Role</label>
                                <input type="text" name="escalation_role" class="form-control form-control-sm" value="<?= htmlspecialchars($rule->escalation_role) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Order</label>
                                <input type="number" name="sort_order" class="form-control form-control-sm" value="<?= (int)$rule->sort_order ?>">
                            </div>
                            <div class="col-md-3 d-flex gap-3 align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="active<?= (int)$rule->id ?>" <?= (int)$rule->is_active ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="active<?= (int)$rule->id ?>">Active</label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="business_hours_only" id="hours<?= (int)$rule->id ?>" <?= (int)$rule->business_hours_only ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="hours<?= (int)$rule->id ?>">Business Hours</label>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header">Create Rule</div>
            <div class="card-body">
                <form action="<?= View::url('sla/rules/store') ?>" method="POST" class="row g-3">
                    <?= CSRF::field() ?>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Rule Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Critical SLA" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Warning %</label>
                        <input type="number" min="1" max="99" name="warning_threshold" class="form-control" value="80">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Response Minutes</label>
                        <input type="number" min="1" name="response_time" class="form-control" value="15" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Resolution Minutes</label>
                        <input type="number" min="1" name="resolution_time" class="form-control" value="120" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Escalation Minutes</label>
                        <input type="number" min="0" name="escalation_time" class="form-control" value="30">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Escalation Role</label>
                        <input type="text" name="escalation_role" class="form-control" value="manager">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notify Roles</label>
                        <input type="text" name="notify_roles" class="form-control" value="technician,manager,administrator">
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="newRuleActive" checked>
                            <label class="form-check-label small" for="newRuleActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>At-Risk Tickets</span>
                <a href="<?= View::url('tickets') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-list-ul me-1"></i>Tickets</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Ticket</th>
                                <th>Priority</th>
                                <th>Response</th>
                                <th>Resolution</th>
                                <th>Due</th>
                                <th>Level</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($atRiskTickets)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No tickets are currently at SLA risk.</td></tr>
                        <?php else: foreach ($atRiskTickets as $ticket): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= View::url('tickets/' . $ticket->id) ?>" class="fw-bold text-primary"><?= htmlspecialchars($ticket->ticket_number) ?></a>
                                    <small class="d-block text-muted text-truncate" style="max-width:240px"><?= htmlspecialchars($ticket->title) ?></small>
                                </td>
                                <td><?= View::priorityBadge($ticket->priority) ?></td>
                                <td><?= View::statusBadge($ticket->response_sla_status ?? 'on_track') ?><small class="d-block text-muted"><?= View::date($ticket->response_due_at, 'M d H:i') ?></small></td>
                                <td><?= View::statusBadge($ticket->resolution_sla_status ?? 'on_track') ?><small class="d-block text-muted"><?= View::date($ticket->resolution_due_at ?: $ticket->sla_due_at, 'M d H:i') ?></small></td>
                                <td><span class="<?= ($ticket->sla_status ?? '') === 'breached' ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $formatMinutes(max(0, (int)ceil((strtotime($ticket->resolution_due_at ?: $ticket->sla_due_at ?: date('Y-m-d H:i:s')) - time()) / 60))) ?></span></td>
                                <td><span class="badge bg-light text-dark"><?= (int)($ticket->escalation_level ?? 0) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card shadow-sm border-0">
            <div class="card-header">Recent SLA Events</div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <p class="text-muted small text-center mb-0">No SLA events recorded yet.</p>
                <?php else: foreach ($events as $event): ?>
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2 gap-3">
                        <div>
                            <a href="<?= View::url('tickets/' . $event->ticket_id) ?>" class="fw-semibold"><?= htmlspecialchars($event->ticket_number) ?></a>
                            <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $event->event_type))) ?></span>
                            <small class="d-block text-muted"><?= htmlspecialchars($event->notes ?? $event->title ?? '') ?></small>
                        </div>
                        <small class="text-muted text-nowrap"><?= View::timeAgo($event->created_at) ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
