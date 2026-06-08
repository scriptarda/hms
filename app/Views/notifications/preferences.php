<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Notification Preferences</h1>
        <p>Choose how each alert type reaches you. SMS and push channels are queued for provider integration.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('notifications') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Notification Center</a>
    </div>
</div>

<form action="<?= View::url('notifications/preferences') ?>" method="POST">
    <?= CSRF::field() ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Alert Type</th>
                            <th class="text-center">In-App</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">SMS Ready</th>
                            <th class="text-center">Push Ready</th>
                            <th>Quiet Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($types as $type => $label): $pref = $preferences[$type]; ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold"><?= htmlspecialchars($label) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($type) ?></small>
                            </td>
                            <?php foreach (['in_app','email','sms','push'] as $channel): ?>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-flex">
                                        <input class="form-check-input" type="checkbox" name="preferences[<?= htmlspecialchars($type) ?>][<?= $channel ?>]" <?= (int)$pref->{$channel} ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <div class="d-flex gap-2">
                                    <input type="time" name="preferences[<?= htmlspecialchars($type) ?>][quiet_hours_start]" class="form-control form-control-sm" value="<?= htmlspecialchars(substr((string)($pref->quiet_hours_start ?? ''), 0, 5)) ?>">
                                    <input type="time" name="preferences[<?= htmlspecialchars($type) ?>][quiet_hours_end]" class="form-control form-control-sm" value="<?= htmlspecialchars(substr((string)($pref->quiet_hours_end ?? ''), 0, 5)) ?>">
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <small class="text-muted">Realtime in-app notifications are delivered through Socket.IO when the bridge is running.</small>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Preferences</button>
        </div>
    </div>
</form>
<?php View::endSection(); ?>
