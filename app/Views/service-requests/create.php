<?php use App\Helpers\View; use App\Helpers\CSRF; View::startSection('content');
$schema = $catalogItem->schema ?? [];
$renderField = function (array $field): void {
    $key = $field['key'];
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? ucwords(str_replace('_', ' ', $key));
    $required = !empty($field['required']);
    $placeholder = $field['placeholder'] ?? '';
    $name = 'spec[' . $key . ']';
    ?>
    <div class="col-md-6">
        <label class="form-label fw-semibold"><?= htmlspecialchars($label) ?><?= $required ? ' *' : '' ?></label>
        <?php if ($type === 'select'): ?>
            <select class="form-select" name="<?= htmlspecialchars($name) ?>" <?= $required ? 'required' : '' ?>>
                <option value="">Select <?= htmlspecialchars(strtolower($label)) ?>...</option>
                <?php foreach (($field['options'] ?? []) as $option): ?>
                    <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($type === 'textarea'): ?>
            <textarea class="form-control" name="<?= htmlspecialchars($name) ?>" rows="4" placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $required ? 'required' : '' ?>></textarea>
        <?php else: ?>
            <input type="<?= htmlspecialchars(in_array($type, ['email', 'date', 'number'], true) ? $type : 'text') ?>" class="form-control" name="<?= htmlspecialchars($name) ?>" placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $required ? 'required' : '' ?>>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="text-uppercase text-muted fw-semibold small mb-1"><?= htmlspecialchars($catalogItem->category) ?></div>
        <h1><?= htmlspecialchars($catalogItem->name) ?></h1>
        <p><?= htmlspecialchars($catalogItem->description) ?></p>
    </div>
    <a href="<?= View::url('service-requests/catalog') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Catalog</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <form action="<?= View::url('service-requests/store') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Request Title *</label>
                            <input type="text" class="form-control" name="title" placeholder="Brief subject for tracking and approval" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priority *</label>
                            <select class="form-select" name="priority" required>
                                <?php foreach (['low' => 'Low - Routine', 'medium' => 'Medium - Normal', 'high' => 'High - Urgent', 'critical' => 'Critical - Service impacting'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $catalogItem->default_priority === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Department Owner *</label>
                            <select class="form-select" name="department_id" required>
                                <option value="">Select department...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept->id ?>"><?= htmlspecialchars($dept->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-ui-checks-grid me-1 text-primary"></i>Request Details</h5>
                            <p class="text-muted small mb-0">These fields adjust based on the selected catalog item.</p>
                        </div>
                        <span class="badge bg-light text-dark border"><?= count($schema) ?> required workflow inputs</span>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($schema as $field): ?>
                            <?php $renderField($field); ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold">Additional Context</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Add delivery constraints, operational impact, or special setup notes."></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send-check me-1"></i>Submit Request</button>
                        <a href="<?= View::url('service-requests/catalog') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center text-white" style="width:44px;height:44px;border-radius:8px;background:<?= htmlspecialchars($catalogItem->color) ?>;">
                        <i class="bi <?= htmlspecialchars($catalogItem->icon) ?> fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Workflow Summary</h6>
                        <small class="text-muted"><?= htmlspecialchars($catalogItem->category) ?></small>
                    </div>
                </div>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Approval</span><strong><?= ucwords(str_replace('_', ' ', $catalogItem->approval_mode)) ?></strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Target SLA</span><strong><?= (int)$catalogItem->sla_hours ?> hours</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Default Priority</span><strong><?= ucfirst($catalogItem->default_priority) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
