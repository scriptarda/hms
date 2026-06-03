<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$role = Session::get('role', 'staff');
$canManage = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician']);
$qrUrl = View::url('qr/asset/' . $asset->id);

View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4 text-muted">Asset Tag: <?= htmlspecialchars($asset->asset_tag) ?></span>
            <?= View::statusBadge($asset->status) ?>
        </div>
        <h1><?= htmlspecialchars($asset->name) ?></h1>
    </div>
    <div class="page-actions">
        <?php if ($canManage): ?>
            <a href="<?= View::url('assets/' . $asset->id . '/edit') ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Asset</a>
        <?php endif; ?>
        <a href="<?= View::url('tickets/create?asset_id=' . $asset->id) ?>" class="btn btn-warning text-white btn-sm"><i class="bi bi-exclamation-triangle me-1"></i>Report Incident</a>
        <a href="<?= View::url('assets') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Registry</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Asset Details & Tabs -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Asset Information</h5>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Manufacturer</small>
                        <span class="fw-semibold"><?= htmlspecialchars($asset->manufacturer ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Model</small>
                        <span class="fw-semibold"><?= htmlspecialchars($asset->model ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Serial Number</small>
                        <span class="fw-semibold text-monospace"><?= htmlspecialchars($asset->serial_number ?? '-') ?></span>
                    </div>
                    
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Category</small>
                        <span><?= htmlspecialchars($asset->category_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Department Owner</small>
                        <span><?= htmlspecialchars($asset->department_name ?? '-') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Physical Location</small>
                        <span><?= htmlspecialchars($asset->building_name ?? '-') ?></span>
                    </div>

                    <div class="col-sm-4">
                        <small class="text-muted d-block">Purchase Cost</small>
                        <span class="fw-semibold">$<?= number_format($asset->purchase_cost ?? 0, 2) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Purchase Date</small>
                        <span><?= $asset->purchase_date ? View::date($asset->purchase_date) : '-' ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Warranty Expiry</small>
                        <span class="<?= (strtotime($asset->warranty_expiry) < time() && $asset->warranty_expiry) ? 'text-danger fw-semibold' : 'text-success fw-semibold' ?>">
                            <?= $asset->warranty_expiry ? View::date($asset->warranty_expiry) : '-' ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($asset->notes)): ?>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <small class="text-muted d-block fw-bold mb-1">Specifications / Notes</small>
                        <p class="mb-0 small text-primary-dark whitespace-pre-wrap"><?= htmlspecialchars($asset->notes) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabbed Section -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent p-0 border-bottom">
                <ul class="nav nav-tabs card-header-tabs border-0 m-0" id="assetTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 px-4 border-0" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab"><i class="bi bi-wrench me-2"></i>Maintenance</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 border-0" id="tickets-tab" data-bs-toggle="tab" data-bs-target="#tickets" type="button" role="tab"><i class="bi bi-exclamation-triangle me-2"></i>Incidents</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 border-0" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab"><i class="bi bi-clock-history me-2"></i>Lifecycle Log</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="assetTabsContent">
                    <!-- Maintenance Tab -->
                    <div class="tab-pane fade show active" id="maintenance" role="tabpanel">
                        <?php if (empty($maintenance)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No maintenance schedules or logs recorded.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Task Name</th>
                                            <th>Type</th>
                                            <th>Scheduled Date</th>
                                            <th>Technician</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance as $m): ?>
                                            <tr>
                                                <td><a href="<?= View::url('maintenance/' . $m->id) ?>" class="fw-semibold"><?= htmlspecialchars($m->title) ?></a></td>
                                                <td><small class="text-uppercase"><?= htmlspecialchars($m->type) ?></small></td>
                                                <td><small><?= View::date($m->scheduled_date) ?></small></td>
                                                <td><small><?= htmlspecialchars($m->tech_name ?? 'Unassigned') ?></small></td>
                                                <td><?= View::statusBadge($m->status) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Incidents Tab -->
                    <div class="tab-pane fade" id="tickets" role="tabpanel">
                        <?php if (empty($tickets)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No incident tickets linked to this asset.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $t): ?>
                                            <tr>
                                                <td><a href="<?= View::url('tickets/' . $t->id) ?>" class="fw-bold">#<?= htmlspecialchars($t->ticket_number) ?></a></td>
                                                <td><small class="fw-medium"><?= htmlspecialchars($t->title) ?></small></td>
                                                <td><?= View::priorityBadge($t->priority) ?></td>
                                                <td><?= View::statusDot($t->status) ?></td>
                                                <td><small class="text-muted"><?= View::date($t->created_at) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Lifecycle History Tab -->
                    <div class="tab-pane fade" id="history" role="tabpanel">
                        <div class="timeline-container px-2">
                            <?php if (empty($history)): ?>
                                <p class="text-muted text-center py-4 small mb-0">No lifecycle history log found.</p>
                            <?php else: foreach ($history as $h): ?>
                                <div class="d-flex gap-3 mb-3 align-items-start border-bottom pb-2">
                                    <div class="timeline-icon bg-light text-primary border rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px; flex-shrink:0;">
                                        <i class="bi bi-bookmark-fill small"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold small text-primary-dark"><?= htmlspecialchars($h->description) ?></span>
                                        <small class="text-muted text-uppercase" style="font-size:0.7rem;"><?= htmlspecialchars($h->action) ?> by <?= htmlspecialchars($h->user_name) ?> • <?= View::timeAgo($h->created_at) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: User Assignment & QR Code -->
    <div class="col-lg-4">
        <!-- QR Code Card -->
        <div class="card shadow-sm border-0 mb-4 text-center">
            <div class="card-header bg-transparent py-3 text-start">
                <h6 class="fw-bold mb-0"><i class="bi bi-qr-code me-2 text-primary"></i>Asset Identification Label</h6>
            </div>
            <div class="card-body py-4">
                <!-- QR Code Anchor Container -->
                <div id="qrcode" class="mx-auto mb-3" style="width: 150px; height: 150px;"></div>
                
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($asset->asset_tag) ?></h6>
                <p class="text-muted small mb-3">Scan this code to load details or report incident</p>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="downloadQR"><i class="bi bi-download me-1"></i>Download QR Code</button>
                    <a href="<?= View::url('assets/' . $asset->id . '/qr') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print ID Label</a>
                </div>
            </div>
        </div>

        <!-- User Assignment -->
        <?php if ($canManage): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-check me-2 text-primary"></i>Assign Asset Owner</h6>
            </div>
            <div class="card-body">
                <!-- Active Owner Info -->
                <?php 
                $activeAssignment = array_filter($assignments, fn($a) => $a->returned_at === null);
                $active = reset($activeAssignment);
                ?>
                <div class="p-3 bg-light rounded mb-3 border">
                    <small class="text-muted d-block">Current Assigned Owner</small>
                    <?php if ($active): ?>
                        <span class="fw-bold d-block text-primary-dark"><?= htmlspecialchars($active->user_name) ?></span>
                        <small class="text-muted d-block"><?= htmlspecialchars($active->user_email) ?></small>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Assigned: <?= View::date($active->assigned_at) ?></small>
                    <?php else: ?>
                        <span class="text-muted small fw-medium">No active owner assigned. Currently in storage.</span>
                    <?php endif; ?>
                </div>

                <form action="<?= View::url('assets/' . $asset->id . '/assign') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select User</label>
                        <select class="form-select select2" name="user_id" required>
                            <option value="">Search employee...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u->id ?>" <?= ($active && $active->user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assignment Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Deployment reason, serial matching..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-person-plus me-1"></i>Submit Assignment</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    $(document).ready(function() {
        initSelect2('.select2');

        // Dynamic QR code generation
        const qrContent = <?= json_encode($qrUrl) ?>;
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: qrContent,
            width: 150,
            height: 150,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Download QR image
        $('#downloadQR').on('click', function() {
            const img = $('#qrcode img').attr('src');
            if (img) {
                const link = document.createElement('a');
                link.href = img;
                link.download = 'QR_<?= htmlspecialchars($asset->asset_tag) ?>.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    });
</script>
<?php View::endSection(); ?>
