<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Edit Asset: <?= htmlspecialchars($asset->asset_tag) ?></h1>
    <p>Update identity, ownership, location, warranty, and lifecycle details.</p>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= View::url('assets/' . $asset->id . '/update') ?>" method="POST">
            <?= CSRF::field() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Asset ID</label>
                    <input type="text" class="form-control" value="#<?= (int)$asset->id ?>" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Asset Tag *</label>
                    <input type="text" class="form-control" name="asset_tag" value="<?= htmlspecialchars($asset->asset_tag) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Asset Name *</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($asset->name) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" value="<?= htmlspecialchars($asset->serial_number ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Manufacturer</label>
                    <input type="text" class="form-control" name="manufacturer" value="<?= htmlspecialchars($asset->manufacturer ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Model</label>
                    <input type="text" class="form-control" name="model" value="<?= htmlspecialchars($asset->model ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $asset->category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <select class="form-select" name="status" required>
                        <?php foreach (['active', 'inactive', 'maintenance', 'retired', 'disposed'] as $status): ?>
                            <option value="<?= $status ?>" <?= $asset->status === $status ? 'selected' : '' ?>><?= ucwords($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="fw-bold mb-3">Assignment & Location</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Assigned User</label>
                    <select class="form-select select2" name="assigned_user_id">
                        <option value="">No assigned user</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user->id ?>" <?= ($activeAssignment && $activeAssignment->user_id == $user->id) ? 'selected' : '' ?>><?= htmlspecialchars($user->first_name . ' ' . $user->last_name . ' - ' . $user->email) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= $asset->department_id == $dept->id ? 'selected' : '' ?>><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Assignment Notes</label>
                    <input type="text" class="form-control" name="assignment_notes" placeholder="Reason for ownership change">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="clear_assignment" value="1" id="clearAssignment">
                        <label class="form-check-label" for="clearAssignment">Clear current owner</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Building</label>
                    <select class="form-select" name="building_id" id="buildingId">
                        <option value="">Select building...</option>
                        <?php foreach ($buildings as $bld): ?>
                            <option value="<?= $bld->id ?>" <?= $asset->building_id == $bld->id ? 'selected' : '' ?>><?= htmlspecialchars($bld->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Floor</label>
                    <select class="form-select" name="floor_id" id="floorId"><option value="">Select floor...</option></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Room</label>
                    <select class="form-select" name="room_id" id="roomId"><option value="">Select room...</option></select>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="fw-bold mb-3">Purchase & Warranty</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date" value="<?= htmlspecialchars($asset->purchase_date ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Cost</label>
                    <input type="number" step="0.01" class="form-control" name="purchase_cost" value="<?= htmlspecialchars($asset->purchase_cost ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Warranty Expiry</label>
                    <input type="date" class="form-control" name="warranty_expiry" value="<?= htmlspecialchars($asset->warranty_expiry ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($asset->notes ?? '') ?></textarea>
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
    initSelect2('.select2');
    const floors = <?= json_encode($floors) ?>;
    const rooms = <?= json_encode($rooms) ?>;
    const currentFloorId = <?= json_encode($asset->floor_id) ?>;
    const currentRoomId = <?= json_encode($asset->room_id) ?>;

    function loadFloors(buildingId, selectedId) {
        const $floor = $('#floorId');
        $floor.html('<option value="">Select floor...</option>');
        floors.filter(f => f.building_id == buildingId).forEach(f => {
            $floor.append(`<option value="${f.id}" ${f.id == selectedId ? 'selected' : ''}>${f.name}</option>`);
        });
    }

    function loadRooms(floorId, selectedId) {
        const $room = $('#roomId');
        $room.html('<option value="">Select room...</option>');
        rooms.filter(r => r.floor_id == floorId).forEach(r => {
            $room.append(`<option value="${r.id}" ${r.id == selectedId ? 'selected' : ''}>${r.room_number ? r.room_number + ' - ' : ''}${r.name}</option>`);
        });
    }

    if ($('#buildingId').val()) loadFloors($('#buildingId').val(), currentFloorId);
    if (currentFloorId) loadRooms(currentFloorId, currentRoomId);

    $('#buildingId').on('change', function() {
        loadFloors($(this).val(), null);
        $('#roomId').html('<option value="">Select floor first...</option>');
    });
    $('#floorId').on('change', function() { loadRooms($(this).val(), null); });
    $('#clearAssignment').on('change', function() {
        if (this.checked) $('select[name="assigned_user_id"]').val('').trigger('change');
    });
});
</script>
<?php View::endSection(); ?>
