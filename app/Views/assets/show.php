<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session; use App\Models\Asset;
$role = Session::get('role', 'staff');
$canManage = in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician'], true) || in_array('assets.edit', Session::get('permissions', []), true);
$qrUrl = View::url('qr/asset/' . $asset->id);
$warranty = $warranty ?? Asset::warrantyState($asset->warranty_expiry ?? null);
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="fs-5 text-muted">Asset ID #<?= (int)$asset->id ?></span>
            <span class="badge bg-primary"><?= htmlspecialchars($asset->asset_tag) ?></span>
            <span class="badge bg-<?= $warranty['class'] ?>"><?= htmlspecialchars($warranty['label']) ?></span>
        </div>
        <h1><?= htmlspecialchars($asset->name) ?></h1>
        <p><?= htmlspecialchars(trim(($asset->manufacturer ?? '') . ' ' . ($asset->model ?? '')) ?: 'Asset record') ?></p>
    </div>
    <div class="page-actions">
        <?php if ($canManage): ?>
            <a href="<?= View::url('assets/' . $asset->id . '/edit') ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="<?= View::url('tickets/create?asset_id=' . $asset->id) ?>" class="btn btn-warning text-white btn-sm"><i class="bi bi-exclamation-triangle me-1"></i>Report Incident</a>
        <a href="<?= View::url('assets') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Registry</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Asset Information</h5>
                <div class="row g-3">
                    <div class="col-sm-4"><small class="text-muted d-block">Asset Tag</small><span class="fw-semibold"><?= htmlspecialchars($asset->asset_tag) ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Serial Number</small><span class="fw-semibold"><?= htmlspecialchars($asset->serial_number ?: '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Status</small><?= View::statusDot($asset->status) ?></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Manufacturer</small><span><?= htmlspecialchars($asset->manufacturer ?: '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Model</small><span><?= htmlspecialchars($asset->model ?: '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Category</small><span><?= htmlspecialchars($asset->category_name ?? '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Department</small><span><?= htmlspecialchars($asset->department_name ?? '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Assigned User</small><span><?= htmlspecialchars($asset->assigned_user_name ?? 'Unassigned') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Location</small><span><?= htmlspecialchars(trim(($asset->building_name ?? '') . ' ' . ($asset->floor_name ?? '') . ' ' . ($asset->room_number ?? '') . ' ' . ($asset->room_name ?? '')) ?: '-') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Purchase Date</small><span><?= $asset->purchase_date ? View::date($asset->purchase_date) : '-' ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Purchase Cost</small><span>$<?= number_format((float)($asset->purchase_cost ?? 0), 2) ?></span></div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Warranty Expiry</small>
                        <span class="fw-semibold"><?= $asset->warranty_expiry ? View::date($asset->warranty_expiry) : '-' ?></span>
                        <?php if ($warranty['days'] !== null): ?><small class="d-block text-muted"><?= $warranty['days'] >= 0 ? $warranty['days'] . ' days remaining' : abs($warranty['days']) . ' days expired' ?></small><?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($asset->notes)): ?>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <small class="text-muted d-block fw-bold mb-1">Notes</small>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($asset->notes)) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-transparent p-0 border-bottom">
                <ul class="nav nav-tabs card-header-tabs border-0 m-0" id="assetTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active py-3 px-4 border-0" data-bs-toggle="tab" data-bs-target="#assignments" type="button"><i class="bi bi-person-check me-2"></i>Assignments</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-4 border-0" data-bs-toggle="tab" data-bs-target="#history" type="button"><i class="bi bi-clock-history me-2"></i>History</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-4 border-0" data-bs-toggle="tab" data-bs-target="#maintenance" type="button"><i class="bi bi-wrench me-2"></i>Maintenance</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-4 border-0" data-bs-toggle="tab" data-bs-target="#tickets" type="button"><i class="bi bi-exclamation-triangle me-2"></i>Incidents</button></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="assignments">
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No assignment records yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead><tr><th>User</th><th>Assigned By</th><th>Assigned</th><th>Returned</th><th>Notes</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($a->user_name) ?></strong><small class="d-block text-muted"><?= htmlspecialchars($a->user_email) ?></small></td>
                                            <td><small><?= htmlspecialchars($a->assigned_by_name ?? '-') ?></small></td>
                                            <td><small><?= View::date($a->assigned_at, 'M d, Y H:i') ?></small></td>
                                            <td><small><?= $a->returned_at ? View::date($a->returned_at, 'M d, Y H:i') : 'Active' ?></small></td>
                                            <td><small><?= htmlspecialchars($a->notes ?? '-') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="history">
                        <?php if (empty($history)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No lifecycle history found.</p>
                        <?php else: foreach ($history as $h): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-primary text-white"><i class="bi bi-bookmark-fill"></i></div>
                                <div class="activity-content">
                                    <div class="title"><?= htmlspecialchars($h->description) ?></div>
                                    <div class="desc"><?= htmlspecialchars($h->action) ?> by <?= htmlspecialchars($h->user_name) ?></div>
                                    <div class="time"><?= View::date($h->created_at, 'M d, Y H:i') ?></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <div class="tab-pane fade" id="maintenance">
                        <?php if (empty($maintenance)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No maintenance records.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Task</th><th>Type</th><th>Scheduled</th><th>Technician</th><th>Status</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($maintenance as $m): ?>
                                        <tr>
                                            <td><a href="<?= View::url('maintenance/' . $m->id) ?>" class="fw-semibold"><?= htmlspecialchars($m->title) ?></a></td>
                                            <td><small><?= htmlspecialchars(ucwords($m->type)) ?></small></td>
                                            <td><small><?= View::date($m->scheduled_date) ?></small></td>
                                            <td><small><?= htmlspecialchars($m->tech_name ?? 'Unassigned') ?></small></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $m->status))) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="tickets">
                        <?php if (empty($tickets)): ?>
                            <p class="text-muted text-center py-4 small mb-0">No incidents linked to this asset.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Ticket</th><th>Title</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td><a href="<?= View::url('tickets/' . $t->id) ?>" class="fw-bold"><?= htmlspecialchars($t->ticket_number) ?></a></td>
                                            <td><small><?= htmlspecialchars($t->title) ?></small></td>
                                            <td><?= View::priorityBadge($t->priority) ?></td>
                                            <td><?= View::statusDot($t->status) ?></td>
                                            <td><small><?= View::date($t->created_at) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4 text-center">
            <div class="card-header bg-transparent py-3 text-start"><h6 class="fw-bold mb-0"><i class="bi bi-qr-code me-2 text-primary"></i>QR Code</h6></div>
            <div class="card-body py-4">
                <div id="qrcode" class="mx-auto mb-3" style="width:150px;height:150px;"></div>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($asset->asset_tag) ?></h6>
                <p class="text-muted small mb-3">Scan to view asset details or report an incident.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="downloadQR"><i class="bi bi-download me-1"></i>Download QR</button>
                    <a href="<?= View::url('assets/' . $asset->id . '/qr') ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print Label</a>
                </div>
            </div>
        </div>

        <?php if ($canManage): ?>
            <div class="card mb-4">
                <div class="card-header bg-transparent py-3"><h6 class="fw-bold mb-0"><i class="bi bi-person-check me-2 text-primary"></i>Assignment</h6></div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <small class="text-muted d-block">Current Owner</small>
                        <?php if ($activeAssignment): ?>
                            <strong><?= htmlspecialchars($activeAssignment->user_name) ?></strong>
                            <small class="d-block text-muted"><?= htmlspecialchars($activeAssignment->user_email) ?></small>
                            <small class="d-block text-muted">Since <?= View::date($activeAssignment->assigned_at) ?></small>
                        <?php else: ?>
                            <span class="text-muted small">Unassigned</span>
                        <?php endif; ?>
                    </div>

                    <form action="<?= View::url('assets/' . $asset->id . '/assign') ?>" method="POST" class="mb-3">
                        <?= CSRF::field() ?>
                        <label class="form-label small fw-bold">Assign To</label>
                        <select class="form-select select2 mb-2" name="user_id" required>
                            <option value="">Search user...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u->id ?>" <?= ($activeAssignment && $activeAssignment->user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->first_name . ' ' . $u->last_name . ' - ' . $u->email) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea class="form-control form-control-sm mb-2" name="notes" rows="2" placeholder="Assignment notes"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-person-plus me-1"></i>Assign / Transfer</button>
                    </form>

                    <?php if ($activeAssignment): ?>
                        <form action="<?= View::url('assets/' . $asset->id . '/return') ?>" method="POST" data-confirm="Return this asset from the current owner?">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="notes" value="Returned from detail page">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Return Asset</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-start border-danger border-3">
                <div class="card-header bg-transparent py-3"><h6 class="fw-bold mb-0 text-danger"><i class="bi bi-trash me-2"></i>Registry Removal</h6></div>
                <div class="card-body">
                    <form action="<?= View::url('assets/' . $asset->id . '/delete') ?>" method="POST" data-confirm="Remove this asset from the active registry?">
                        <?= CSRF::field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Soft Delete Asset</button>
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
    const qrContent = <?= json_encode($qrUrl) ?>;
    new QRCode(document.getElementById("qrcode"), {
        text: qrContent,
        width: 150,
        height: 150,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    $('#downloadQR').on('click', function() {
        const img = $('#qrcode img').attr('src');
        if (!img) return;
        const link = document.createElement('a');
        link.href = img;
        link.download = 'QR_<?= htmlspecialchars($asset->asset_tag) ?>.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
<?php View::endSection(); ?>
