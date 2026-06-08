<?php use App\Helpers\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset QR Labels | HEMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#e2e8f0; padding:1rem; }
        .toolbar { max-width:1100px; margin:0 auto 1rem; }
        .label-grid { max-width:1100px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1rem; }
        .label-card { background:#fff; border:1px solid #94a3b8; border-radius:4px; padding:.2in; height:2in; display:flex; justify-content:space-between; align-items:center; font-family:monospace; page-break-inside:avoid; }
        .qr-box { width:1.15in; height:1.15in; flex-shrink:0; }
        @media print {
            body { background:#fff; padding:0; }
            .toolbar { display:none !important; }
            .label-grid { display:grid; grid-template-columns:repeat(2, 3.5in); gap:.15in; margin:0; }
            .label-card { box-shadow:none; width:3.5in; }
        }
    </style>
</head>
<body>
<div class="toolbar d-flex justify-content-between align-items-center gap-2">
    <div>
        <h1 class="h4 mb-0">Asset QR Labels</h1>
        <small class="text-muted"><?= count($assets) ?> labels ready</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer me-1"></i>Print All</button>
        <a href="<?= View::url('assets') ?>" class="btn btn-outline-secondary btn-sm">Registry</a>
    </div>
</div>

<div class="label-grid">
    <?php foreach ($assets as $asset): $qrUrl = View::url('qr/asset/' . $asset->id); ?>
        <div class="label-card">
            <div class="d-flex flex-column justify-content-between h-100 pe-2">
                <div>
                    <div style="font-weight:800;font-size:14px;text-transform:uppercase;">HealthCentral</div>
                    <div style="font-size:8px;color:#64748b;">Asset QR Label</div>
                </div>
                <div style="font-size:10px;">
                    <strong>ID:</strong> <?= (int)$asset->id ?><br>
                    <strong>TAG:</strong> <?= htmlspecialchars($asset->asset_tag) ?><br>
                    <strong>NAME:</strong> <?= htmlspecialchars(substr($asset->name, 0, 20)) ?><br>
                    <strong>SN:</strong> <?= htmlspecialchars(substr($asset->serial_number ?? '-', 0, 18)) ?>
                </div>
                <div style="font-size:8px;color:#64748b;">Scan to view or report issue</div>
            </div>
            <div class="qr-box" data-qr="<?= htmlspecialchars($qrUrl) ?>"></div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.qr-box').forEach(function(box) {
    new QRCode(box, {
        text: box.dataset.qr,
        width: 110,
        height: 110,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>
</body>
</html>
