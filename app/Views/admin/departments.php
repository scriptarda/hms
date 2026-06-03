<?php use App\Helpers\View; use App\Helpers\Database;
// Fetch buildings layout recursively for display
$blds = Database::getInstance()->fetchAll("SELECT * FROM buildings WHERE deleted_at IS NULL ORDER BY name");
$flrs = Database::getInstance()->fetchAll("SELECT f.*, b.name as building_name FROM floors f JOIN buildings b ON f.building_id=b.id WHERE f.deleted_at IS NULL ORDER BY b.name, f.floor_number");
$rms = Database::getInstance()->fetchAll("SELECT r.*, f.name as floor_name, b.name as building_name FROM rooms r JOIN floors f ON r.floor_id=f.id JOIN buildings b ON f.building_id=b.id WHERE r.deleted_at IS NULL ORDER BY b.name, f.floor_number, r.name");
View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Departments & Facilities</h1>
        <p>Manage budget centers, department heads, and physical campus layouts (buildings, floors, rooms).</p>
    </div>
    <a href="<?= View::url('admin/departments/create') ?>" class="btn btn-primary"><i class="bi bi-building-add me-1"></i>Add DepartmentCenter</a>
</div>

<div class="row g-4">
    <!-- Left Column: Department List -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title fw-bold mb-0">Hospital Departments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Code</th>
                                <th>Department Name</th>
                                <th>Department Head</th>
                                <th>Email / Phone</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-monospace"><?= htmlspecialchars($dept->code) ?></td>
                                    <td class="fw-semibold text-primary-dark"><?= htmlspecialchars($dept->name) ?></td>
                                    <td><small><?= htmlspecialchars($dept->head_name ?? 'Not assigned') ?></small></td>
                                    <td>
                                        <small class="text-muted d-block"><?= htmlspecialchars($dept->email ?? '-') ?></small>
                                        <small class="text-muted"><?= htmlspecialchars($dept->phone ?? '-') ?></small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?= View::statusDot($dept->is_active ? 'active' : 'inactive') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Location Structure -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Campus Topology</h5>
            </div>
            <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                <div class="p-3">
                    <?php if (empty($blds)): ?>
                        <p class="text-muted text-center py-4 small mb-0">No buildings registered yet.</p>
                    <?php else: foreach ($blds as $b): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold mb-2 text-primary-dark"><i class="bi bi-building me-1"></i><?= htmlspecialchars($b->name) ?> <small class="text-muted text-monospace">(<?= htmlspecialchars($b->code) ?>)</small></h6>
                            
                            <!-- Floors under building -->
                            <div class="ps-3 border-start ms-2">
                                <?php 
                                $bFloors = array_filter($flrs, fn($f) => $f->building_id == $b->id);
                                if (empty($bFloors)): ?>
                                    <small class="text-muted d-block">No floors added.</small>
                                <?php else: foreach ($bFloors as $f): ?>
                                    <div class="mb-2">
                                        <span class="d-block small fw-semibold text-primary"><i class="bi bi-layers me-1"></i><?= htmlspecialchars($f->name) ?></span>
                                        
                                        <!-- Rooms under floor -->
                                        <div class="ps-3 border-start ms-2 mt-1">
                                            <?php 
                                            $fRooms = array_filter($rms, fn($r) => $r->floor_id == $f->id);
                                            if (empty($fRooms)): ?>
                                                <small class="text-muted" style="font-size:0.75rem;">No rooms added.</small>
                                            <?php else: foreach ($fRooms as $r): ?>
                                                <small class="text-muted d-inline-block me-3" style="font-size:0.75rem;"><i class="bi bi-door-closed me-1"></i><?= htmlspecialchars($r->room_number ? $r->room_number . ' - ' : '') ?><?= htmlspecialchars($r->name) ?></small>
                                            <?php endforeach; endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
