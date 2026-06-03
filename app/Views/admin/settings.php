<?php use App\Helpers\View; use App\Helpers\CSRF;
$cfg = $GLOBALS['appConfig'];
View::startSection('content'); ?>
<div class="page-header">
    <h1>System Settings</h1>
    <p>Configure global application parameters, security controls, and support desk SLA response deadlines.</p>
</div>

<form action="<?= View::url('admin/settings') ?>" method="POST">
    <?= CSRF::field() ?>

    <div class="row g-4">
        <!-- Branding Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-palette-fill me-2 text-primary"></i>Application Branding</h5>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <div>
                        <label class="form-label fw-bold">Branding Name *</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($cfg['name']) ?>" required>
                    </div>
                    <div>
                        <label class="form-label fw-bold">Application Base URL *</label>
                        <input type="url" class="form-control" name="url" value="<?= htmlspecialchars($cfg['url']) ?>" required>
                    </div>
                    
                    <hr class="border-light my-2">
                    <h6 class="fw-bold"><i class="bi bi-shield-exclamation me-1 text-warning"></i>Login Throttling Security</h6>
                    
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Max Login Attempts</label>
                            <input type="number" class="form-control form-control-sm" name="max_attempts" value="<?= $cfg['login_throttle']['max_attempts'] ?>" required min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Lockout Time (Seconds)</label>
                            <input type="number" class="form-control form-control-sm" name="lockout_time" value="<?= $cfg['login_throttle']['lockout_time'] ?>" required min="60">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-stopwatch-fill me-2 text-primary"></i>Service Level Agreement (SLA) Targets</h5>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <p class="text-muted small">Specify default target resolution times (in minutes) for each incident priority level.</p>
                    
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-danger">CRITICAL SLA (Minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="sla_critical" value="<?= $cfg['sla_defaults']['critical'] ?>" required min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-warning">HIGH SLA (Minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="sla_high" value="<?= $cfg['sla_defaults']['high'] ?>" required min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-primary">MEDIUM SLA (Minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="sla_medium" value="<?= $cfg['sla_defaults']['medium'] ?>" required min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-secondary">LOW SLA (Minutes)</label>
                            <input type="number" class="form-control form-control-sm" name="sla_low" value="<?= $cfg['sla_defaults']['low'] ?>" required min="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Panel -->
        <div class="col-12 mt-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Configuration</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php View::endSection(); ?>
