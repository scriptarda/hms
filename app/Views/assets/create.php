<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content'); ?>
<div class="page-header">
    <h1>Register New Asset</h1>
    <p>Add medical devices, computing hardware, and facility assets to the registry.</p>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= View::url('assets/store') ?>" method="POST">
            <?= CSRF::field() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Asset ID</label>
                    <input type="text" class="form-control" value="Auto generated" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Asset Tag *</label>
                    <input type="text" class="form-control" name="asset_tag" placeholder="e.g. RAD-MRI-01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Asset Name *</label>
                    <input type="text" class="form-control" name="name" placeholder="e.g. Siemens Magnetom MRI" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" placeholder="SN-...">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Manufacturer</label>
                    <input type="text" class="form-control" name="manufacturer" placeholder="e.g. Siemens">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Model</label>
                    <input type="text" class="form-control" name="model" placeholder="e.g. Magnetom Lumina">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">Select category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="retired">Retired</option>
                        <option value="disposed">Disposed</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="fw-bold mb-3">Assignment & Location</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Assigned User</label>
                    <select class="form-select select2" name="assigned_user_id">
                        <option value="">Leave unassigned</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user->id ?>"><?= htmlspecialchars($user->first_name . ' ' . $user->last_name . ' - ' . $user->email) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Assignment Notes</label>
                    <input type="text" class="form-control" name="assignment_notes" placeholder="Initial owner, handoff notes, or deployment reason">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Building</label>
                    <select class="form-select" name="building_id" id="buildingId">
                        <option value="">Select building...</option>
                        <?php foreach ($buildings as $bld): ?>
                            <option value="<?= $bld->id ?>"><?= htmlspecialchars($bld->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Floor</label>
                    <select class="form-select" name="floor_id" id="floorId"><option value="">Select building first...</option></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Room</label>
                    <select class="form-select" name="room_id" id="roomId"><option value="">Select floor first...</option></select>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="fw-bold mb-3">Purchase & Warranty</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Purchase Cost</label>
                    <input type="number" step="0.01" class="form-control" name="purchase_cost" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Warranty Expiry</label>
                    <input type="date" class="form-control" name="warranty_expiry">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Specs, warranty terms, vendor, or lifecycle notes"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Register Asset</button>
                <a href="<?= View::url('assets') ?>" class="btn btn-outline-secondary">Cancel</a>
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

    $('#buildingId').on('change', function() {
        const bId = $(this).val();
        const $floor = $('#floorId');
        $('#roomId').html('<option value="">Select floor first...</option>');
        $floor.html('<option value="">Select floor...</option>');
        floors.filter(f => f.building_id == bId).forEach(f => $floor.append(`<option value="${f.id}">${f.name}</option>`));
    });

    $('#floorId').on('change', function() {
        const fId = $(this).val();
        const $room = $('#roomId');
        $room.html('<option value="">Select room...</option>');
        rooms.filter(r => r.floor_id == fId).forEach(r => $room.append(`<option value="${r.id}">${r.room_number ? r.room_number + ' - ' : ''}${r.name}</option>`));
    });
});
</script>
<?php View::endSection(); ?>
