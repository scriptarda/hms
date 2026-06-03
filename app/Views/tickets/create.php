<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Database; use App\Helpers\Session;

// Fetch subcategories
$subcategories = Database::getInstance()->fetchAll("SELECT id, category_id, name FROM ticket_subcategories WHERE is_active=1 AND deleted_at IS NULL ORDER BY name");
$role = Session::get('role', 'staff');
$canAssign = in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator']);

View::startSection('content'); ?>
<div class="page-header">
    <h1>Create Incident Ticket</h1>
    <p>Report a new hardware, software, clinical equipment, or facilities issue.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="<?= View::url('tickets/store') ?>" method="POST" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            
            <div class="row g-3 mb-4">
                <!-- Title -->
                <div class="col-12">
                    <label class="form-label fw-bold">Issue Title / Subject</label>
                    <input type="text" class="form-control" name="title" placeholder="Brief summary of the issue (e.g. Siemens MRI calibration error)" required>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-bold">Detailed Description</label>
                    <textarea class="form-control" name="description" rows="5" placeholder="Please describe the issue in detail. Include any error codes, symptoms, and exact sequence of events." required></textarea>
                </div>

                <!-- Category & Subcategory -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category_id" id="categoryId" required>
                        <option value="">Select Category...</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Subcategory</label>
                    <select class="form-select" name="subcategory_id" id="subcategoryId">
                        <option value="">Select Category First...</option>
                    </select>
                </div>

                <!-- Priority -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Priority</label>
                    <select class="form-select" name="priority" required>
                        <option value="low">Low - Routine issue</option>
                        <option value="medium" selected>Medium - Normal priority</option>
                        <option value="high">High - Urgent clinical/operational impact</option>
                        <option value="critical">Critical - Patient safety or systemic outage</option>
                    </select>
                </div>

                <!-- Asset Linkage -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Linked Asset</label>
                    <select class="form-select select2" name="asset_id" id="assetId">
                        <option value="">None / Search Asset Tag...</option>
                        <?php foreach($assets as $ast): ?>
                            <option value="<?= $ast->id ?>"><?= htmlspecialchars($ast->asset_tag . ' — ' . $ast->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Department & Location -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department...</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Building</label>
                    <select class="form-select" name="building_id">
                        <option value="">Select Building...</option>
                        <?php foreach($buildings as $bld): ?>
                            <option value="<?= $bld->id ?>"><?= htmlspecialchars($bld->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Attachment</label>
                    <input type="file" class="form-control" name="attachment">
                    <div class="form-text">Attach log, screenshot, or document.</div>
                </div>

                <!-- Tech Assignment (If Authorized) -->
                <?php if ($canAssign): ?>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Assign Immediate Technician</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">Unassigned (Queue)</option>
                        <?php foreach($technicians as $tech): ?>
                            <option value="<?= $tech->id ?>"><?= htmlspecialchars($tech->first_name . ' ' . $tech->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Ticket</button>
                <a href="<?= View::url('tickets') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        // Initialize select2
        initSelect2('.select2');

        // Subcategory dynamic filtering
        const subcategories = <?= json_encode($subcategories) ?>;
        $('#categoryId').on('change', function() {
            const catId = $(this).val();
            const $sub = $('#subcategoryId');
            $sub.html('<option value="">Select Subcategory...</option>');
            
            if (catId) {
                const filtered = subcategories.filter(s => s.category_id == catId);
                filtered.forEach(s => {
                    $sub.append(`<option value="${s.id}">${s.name}</option>`);
                });
            } else {
                $sub.html('<option value="">Select Category First...</option>');
            }
        });

        // Pre-fill asset if passed in URL
        const urlParams = new URLSearchParams(window.location.search);
        const assetId = urlParams.get('asset_id');
        if (assetId) {
            $('#assetId').val(assetId).trigger('change');
        }
    });
</script>
<?php View::endSection(); ?>
