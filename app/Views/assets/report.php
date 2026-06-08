<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$isLoggedIn = Session::isLoggedIn();
$user = Session::get('user', []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Report Issue | <?= htmlspecialchars($asset->asset_tag) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family:Inter, sans-serif; background:#eef2f7; color:#0f172a; margin:0; }
        .mobile-shell { max-width:520px; min-height:100vh; margin:0 auto; background:#fff; display:flex; flex-direction:column; }
        .header { background:#0f766e; color:#fff; padding:1.5rem; }
        .header h1 { font-size:1.4rem; font-weight:800; margin:0; }
        .content { padding:1rem; flex:1; }
        .asset-card { border:1px solid #e2e8f0; border-radius:8px; padding:1rem; background:#f8fafc; }
        .btn-submit { min-height:48px; font-weight:700; }
    </style>
</head>
<body>
<div class="mobile-shell">
    <header class="header">
        <a href="<?= View::url('qr/asset/' . $asset->id) ?>" class="text-white text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Asset details</a>
        <h1 class="mt-3">Report Asset Issue</h1>
        <div class="opacity-75 small"><?= htmlspecialchars($asset->asset_tag) ?> - <?= htmlspecialchars($asset->name) ?></div>
    </header>

    <main class="content">
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>

        <div class="asset-card mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width:42px;height:42px;"><i class="bi bi-hdd-stack fs-4"></i></div>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($asset->name) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($asset->manufacturer ?? '-') ?> <?= htmlspecialchars($asset->model ?? '') ?></small>
                </div>
            </div>
        </div>

        <form action="<?= View::url('qr/asset/' . $asset->id . '/report') ?>" method="POST">
            <?= CSRF::field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Issue Title *</label>
                <input type="text" name="title" class="form-control" value="Issue with <?= htmlspecialchars($asset->asset_tag) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Priority *</label>
                <select name="priority" class="form-select" required>
                    <option value="medium" selected>Medium - Needs attention</option>
                    <option value="high">High - Clinical or operational impact</option>
                    <option value="critical">Critical - Patient safety or outage</option>
                    <option value="low">Low - Routine</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">What is happening? *</label>
                <textarea name="description" rows="5" class="form-control" placeholder="Describe symptoms, error codes, sounds, visible damage, or when the issue started." required></textarea>
            </div>

            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label fw-semibold">Your Name</label>
                    <input type="text" name="reporter_name" class="form-control" value="<?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Your Email</label>
                    <input type="email" name="reporter_email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Phone / Extension</label>
                    <input type="text" name="reporter_phone" class="form-control" placeholder="Optional">
                </div>
            </div>

            <button class="btn btn-danger btn-submit w-100 mt-4"><i class="bi bi-send-check me-1"></i>Create Linked Ticket</button>
            <?php if (!$isLoggedIn): ?>
                <p class="text-muted small text-center mt-3 mb-0">You can submit without logging in. Reporter details are saved inside the ticket.</p>
            <?php endif; ?>
        </form>
    </main>
</div>
</body>
</html>
