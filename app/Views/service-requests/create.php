<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p>Submit request. Requires authorization from your department supervisor.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('service-requests/store') ?>" method="POST">
            <?= CSRF::field() ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

            <div class="row g-3 mb-4">
                <!-- Generic fields -->
                <div class="col-md-8">
                    <label class="form-label fw-bold">Request Title *</label>
                    <input type="text" class="form-control" name="title" placeholder="Brief subject (e.g. Standard PC setup for nurse station)" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Priority *</label>
                    <select class="form-select" name="priority" required>
                        <option value="low">Low - Routine</option>
                        <option value="medium" selected>Medium - Normal</option>
                        <option value="high">High - Critical need</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Department Owner</label>
                    <select class="form-select" name="department_id" required>
                        <option value="">Select department matching this budget/location...</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Custom specifications based on type -->
                <div class="col-12 mt-4">
                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-gear-wide-connected me-1"></i>Custom Request Specifications</h5>
                    <div class="row g-3">
                        <?php if ($type === 'new_computer'): ?>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Device Type</label>
                                <select class="form-select form-select-sm" name="spec[device_type]">
                                    <option value="desktop">Standard Workstation Desktop</option>
                                    <option value="laptop">Portable Laptop (Clinical Staff)</option>
                                    <option value="tablet">Clinical Tablet (Rounds)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Processor Specs</label>
                                <select class="form-select form-select-sm" name="spec[processor]">
                                    <option value="standard">Intel Core i5 (Standard Wards)</option>
                                    <option value="performance">Intel Core i7 (Administration / Heavy)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Memory (RAM)</label>
                                <select class="form-select form-select-sm" name="spec[memory]">
                                    <option value="8gb">8 GB DDR4</option>
                                    <option value="16gb">16 GB DDR4</option>
                                    <option value="32gb">32 GB DDR4</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Primary Disk Storage</label>
                                <select class="form-select form-select-sm" name="spec[storage]">
                                    <option value="256gb_ssd">256 GB NVMe SSD</option>
                                    <option value="512gb_ssd">512 GB NVMe SSD</option>
                                    <option value="1tb_ssd">1 TB NVMe SSD</option>
                                </select>
                            </div>

                        <?php elseif ($type === 'software_install'): ?>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Application / Software Name</label>
                                <input type="text" class="form-control form-control-sm" name="spec[software_name]" placeholder="e.g. Adobe Acrobat Pro or PACS client" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Target System OS</label>
                                <select class="form-select form-select-sm" name="spec[target_os]">
                                    <option value="windows_10">Windows 10/11 Enterprise</option>
                                    <option value="macos">macOS</option>
                                    <option value="ios">iOS (Clinical iPad)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Host Workstation Asset Tag</label>
                                <input type="text" class="form-control form-control-sm" name="spec[host_asset_tag]" placeholder="e.g. IT-LAP-0421">
                                <div class="form-text">Specify asset tag where software should be installed.</div>
                            </div>

                        <?php elseif ($type === 'email_setup'): ?>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Employee First Name</label>
                                <input type="text" class="form-control form-control-sm" name="spec[employee_first_name]" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Employee Last Name</label>
                                <input type="text" class="form-control form-control-sm" name="spec[employee_last_name]" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Desired Username Prefix</label>
                                <input type="text" class="form-control form-control-sm" name="spec[desired_username]" placeholder="e.g. jdoe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Alternate Verification Email</label>
                                <input type="email" class="form-control form-control-sm" name="spec[alternate_email]" placeholder="e.g. personal@gmail.com" required>
                            </div>

                        <?php elseif ($type === 'access_request'): ?>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Target System</label>
                                <select class="form-select form-select-sm" name="spec[target_system]">
                                    <option value="emr_records">Electronic Medical Records (EMR)</option>
                                    <option value="pacs_imaging">PACS Radiology Imaging</option>
                                    <option value="pharmacy_dispensary">Pharmacy Dispensary System</option>
                                    <option value="billing_finance">Finance & Billing Module</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Access Level Group</label>
                                <select class="form-select form-select-sm" name="spec[access_level]">
                                    <option value="read_only">Read-Only (Viewer)</option>
                                    <option value="write_standard">Standard Staff (Read/Write)</option>
                                    <option value="supervisor_admin">Supervisor / Administrator Role</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Access Justification</label>
                                <textarea class="form-control form-control-sm" name="spec[justification]" rows="3" placeholder="Provide clinical or operational reasoning why this access is required..." required></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <label class="form-label fw-bold">Additional Comments / Context</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Describe any delivery deadlines or specific setup preferences..."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Submit Catalog Request</button>
                <a href="<?= View::url('service-requests/catalog') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
