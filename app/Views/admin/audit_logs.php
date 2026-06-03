<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header text-start">
    <h1>System Audit Trail</h1>
    <p>Chronological security logs of logins, ticket updates, permission matrices changes, and administrative actions.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="auditLogsTable">
                <thead>
                    <tr>
                        <th class="ps-4">Logged Time</th>
                        <th>User Account</th>
                        <th>Security Action</th>
                        <th>Entity Target</th>
                        <th>IP Address</th>
                        <th class="pe-4">User Agent Browser</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted small">No audit log history records found.</td>
                        </tr>
                    <?php else: foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-4"><small class="text-muted"><?= View::date($log->created_at, 'Y-m-d H:i:s') ?></small></td>
                            <td><small class="fw-bold"><?= htmlspecialchars($log->user_name ?? 'System (Guest)') ?></small></td>
                            <td>
                                <?php
                                $badgeColor = 'secondary';
                                if ($log->action === 'login') $badgeColor = 'success';
                                elseif ($log->action === 'login_failed') $badgeColor = 'danger';
                                elseif ($log->action === 'create') $badgeColor = 'primary';
                                elseif ($log->action === 'update') $badgeColor = 'warning text-dark';
                                elseif ($log->action === 'delete') $badgeColor = 'danger';
                                ?>
                                <span class="badge bg-<?= $badgeColor ?> text-uppercase"><?= htmlspecialchars($log->action) ?></span>
                            </td>
                            <td>
                                <small class="text-monospace">
                                    <?= htmlspecialchars($log->entity_type ?? 'None') ?>
                                    <?php if ($log->entity_id): ?>
                                        (ID: <?= $log->entity_id ?>)
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><small class="text-monospace"><?= htmlspecialchars($log->ip_address) ?></small></td>
                            <td class="pe-4"><small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($log->user_agent) ?>"><?= htmlspecialchars($log->user_agent) ?></small></td>
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
        $('#auditLogsTable').DataTable({
            ordering: true,
            info: true,
            paging: true
        });
    });
</script>
<?php View::endSection(); ?>
