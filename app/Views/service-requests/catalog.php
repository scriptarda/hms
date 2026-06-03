<?php use App\Helpers\View; View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Service Catalog</h1>
        <p>Browse and request equipment provisions, system access credentials, software, and tools.</p>
    </div>
    <a href="<?= View::url('service-requests') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-check me-1"></i>Track My Requests</a>
</div>

<div class="row g-4">
    <!-- Card: Hardware Provisioning -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary-light text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width:60px; height:60px; font-size: 1.5rem;">
                    <i class="bi bi-pc-display"></i>
                </div>
                <h5 class="fw-bold mb-2">New Computer</h5>
                <p class="text-muted small mb-4">Request a new workstation, laptop, clinical terminal, or monitor setup.</p>
                <a href="<?= View::url('service-requests/create/new_computer') ?>" class="btn btn-primary btn-sm w-100">Select Request</a>
            </div>
        </div>
    </div>

    <!-- Card: Software Installation -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mx-auto mb-3" style="width:60px; height:60px; font-size: 1.5rem;">
                    <i class="bi bi-cloud-download"></i>
                </div>
                <h5 class="fw-bold mb-2">Software Install</h5>
                <p class="text-muted small mb-4">Request licensed software installations, EMR upgrades, or utility tools.</p>
                <a href="<?= View::url('service-requests/create/software_install') ?>" class="btn btn-success btn-sm w-100 text-white">Select Request</a>
            </div>
        </div>
    </div>

    <!-- Card: Email Account -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width:60px; height:60px; font-size: 1.5rem;">
                    <i class="bi bi-envelope-at"></i>
                </div>
                <h5 class="fw-bold mb-2">Email Setup</h5>
                <p class="text-muted small mb-4">Request new staff email inbox setup or distribution list modifications.</p>
                <a href="<?= View::url('service-requests/create/email_setup') ?>" class="btn btn-warning btn-sm w-100 text-white">Select Request</a>
            </div>
        </div>
    </div>

    <!-- Card: System Access Credentials -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-purple-subtle text-purple d-flex align-items-center justify-content-center mx-auto mb-3" style="width:60px; height:60px; font-size: 1.5rem; background:#f3e8ff; color:#a855f7;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h5 class="fw-bold mb-2">Access Request</h5>
                <p class="text-muted small mb-4">Request badge security authorization, VPN setup, or clinical system logins.</p>
                <a href="<?= View::url('service-requests/create/access_request') ?>" class="btn btn-sm w-100 text-white" style="background:#a855f7;">Select Request</a>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
