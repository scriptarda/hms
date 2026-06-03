<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Database;
$floors = Database::getInstance()->fetchAll("SELECT id, building_id, name FROM floors WHERE deleted_at IS NULL");
$rooms = Database::getInstance()->fetchAll("SELECT r.id, r.name, r.room_number, f.building_id, r.floor_id FROM rooms r JOIN floors f ON r.floor_id = f.id WHERE r.deleted_at IS NULL");
View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Asset: <?= htmlspecialchars($asset->asset_tag) ?></h1>
    <p>Update deployment details, status, warranty, or general configuration.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('assets/' . $asset->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-4">
                <!-- Asset Info -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Asset Tag *</label>
                    <input type="text" class="form-control" name="asset_tag" value="<?= htmlspecialchars($asset->asset_tag) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Asset Name *</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($asset->name) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" value="<?= htmlspecialchars($asset->serial_number) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select Category...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $asset->category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Manufacturer</label>
                    <input type="text" class="form-control" name="manufacturer" value="<?= htmlspecialchars($asset->manufacturer) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Model</label>
                    <input type="text" class="form-control" name="model" value="<?= htmlspecialchars($asset->model) ?>">
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Deployment & Physical Location</h5>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Department Owner</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= $asset->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Building</label>
                    <select class="form-select" name="building_id" id="buildingId">
                        <option value="">Select Building...</option>
                        <?php foreach($buildings as $bld): ?>
                            <option value="<?= $bld->id ?>" <?= $asset->building_id == $bld->id ? 'selected' : '' ?>><?= htmlspecialchars($bld->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Floor</label>
                    <select class="form-select" name="floor_id" id="floorId">
                        <option value="">Select Floor...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Room</label>
                    <select class="form-select" name="room_id" id="roomId">
                        <option value="">Select Room...</option>
                    </select>
                </div>

                <hr class="my-4 border-light">
                <h5 class="fw-bold">Lifecycle & Financials</h5>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="active" <?= $asset->status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $asset->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="maintenance" <?= $asset->status === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="retired" <?= $asset->status === 'retired' ? 'selected' : '' ?>>Retired</option>
                        <option value="disposed" <?= $asset->status === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date" value="<?= $asset->purchase_date ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="purchase_cost" value="<?= $asset->purchase_cost ?>" placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Warranty Expiry Date</label>
                    <input type="date" class="form-control" name="warranty_expiry" value="<?= $asset->warranty_expiry ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($asset->notes) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                <a href="<?= View::url('assets/' . $asset->id) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        const floors = <?= json_encode($floors) ?>;
        const rooms = <?= json_encode($rooms) ?>;

        const currentFloorId = <?= json_encode($asset->floor_id) ?>;
        const currentRoomId = <?= json_encode($asset->room_id) ?>;

        function loadFloors(bId, selectedId) {
            const $floor = $('#floorId');
            const $room = $('#roomId');
            $floor.html('<option value="">Select Floor...</option>');
            $room.html('<option value="">Select Floor First...</option>');

            if (bId) {
                const bFloors = floors.filter(f => f.building_id == bId);
                bFloors.forEach(f => {
                    const sel = f.id == selectedId ? 'selected' : '';
                    $floor.append(`<option value="${f.id}" ${sel}>${f.name}</option>`);
                });
            } else {
                $floor.html('<option value="">Select Building First...</option>');
            }
        }

        function loadRooms(fId, selectedId) {
            const $room = $('#roomId');
            $room.html('<option value="">Select Room...</option>');

            if (fId) {
                const fRooms = rooms.filter(r => r.floor_id == fId);
                fRooms.forEach(r => {
                    const sel = r.id == selectedId ? 'selected' : '';
                    $room.append(`<option value="${r.id}" ${sel}>${r.room_number ? r.room_number + ' - ' : ''}${r.name}</option>`);
                });
            } else {
                $room.html('<option value="">Select Floor First...</option>');
            }
        }

        // Initialize selectors
        const initialBuilding = $('#buildingId').val();
        if (initialBuilding) {
            loadFloors(initialBuilding, currentFloorId);
            if (currentFloorId) {
                loadRooms(currentFloorId, currentRoomId);
            }
        }

        $('#buildingId').on('change', function() {
            loadFloors($(this).val(), null);
        });

        $('#floorId').on('change', function() {
            loadRooms($(this).val(), null);
        });
    });
</script>
<?php View::endSection(); ?>
