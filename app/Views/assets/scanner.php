<?php use App\Helpers\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scan Asset QR | HEMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin:0; min-height:100vh; font-family:Inter, sans-serif; background:#0f172a; color:#fff; }
        .scan-shell { max-width:520px; min-height:100vh; margin:0 auto; display:flex; flex-direction:column; background:#101827; }
        .scan-header { padding:1.25rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .scan-title h1 { font-size:1.35rem; font-weight:800; margin:0; }
        .scan-title p { color:#94a3b8; margin:.25rem 0 0; font-size:.85rem; }
        .camera-wrap { position:relative; flex:1; min-height:420px; background:#020617; overflow:hidden; }
        video { width:100%; height:100%; object-fit:cover; position:absolute; inset:0; }
        .scan-frame { position:absolute; inset:15%; border:3px solid #22c55e; border-radius:16px; box-shadow:0 0 0 999px rgba(2,6,23,.55); }
        .scan-line { position:absolute; left:16%; right:16%; top:22%; height:2px; background:#22c55e; box-shadow:0 0 14px #22c55e; animation:scan 2.2s linear infinite; }
        @keyframes scan { 0%{transform:translateY(0)} 50%{transform:translateY(250px)} 100%{transform:translateY(0)} }
        .scan-status { position:absolute; left:1rem; right:1rem; bottom:1rem; background:rgba(15,23,42,.9); border:1px solid rgba(148,163,184,.25); border-radius:12px; padding:.85rem; font-size:.9rem; }
        .scan-actions { padding:1rem; background:#fff; color:#0f172a; }
        .manual-card { border:1px solid #e2e8f0; border-radius:8px; padding:1rem; background:#f8fafc; }
        canvas { display:none; }
    </style>
</head>
<body>
<div class="scan-shell">
    <header class="scan-header">
        <div class="scan-title">
            <h1>Scan Asset QR</h1>
            <p>Point your camera at an asset label.</p>
        </div>
        <a href="<?= View::url('/') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-house-door"></i></a>
    </header>

    <main class="camera-wrap">
        <video id="preview" muted playsinline></video>
        <canvas id="scanCanvas"></canvas>
        <div class="scan-frame"></div>
        <div class="scan-line"></div>
        <div class="scan-status" id="scanStatus">
            <i class="bi bi-camera-video me-1"></i> Starting camera...
        </div>
    </main>

    <section class="scan-actions">
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>
        <div class="manual-card">
            <label class="form-label fw-semibold small">Manual Asset ID or Tag</label>
            <div class="input-group">
                <input type="text" class="form-control" id="manualAsset" placeholder="e.g. 12 or MRI-SCAN-01">
                <button class="btn btn-primary" id="manualGo"><i class="bi bi-arrow-right"></i></button>
            </div>
            <div class="form-text">Use this if camera access is blocked or the label is damaged.</div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(function() {
    const video = document.getElementById('preview');
    const canvas = document.getElementById('scanCanvas');
    const statusEl = document.getElementById('scanStatus');
    const ctx = canvas.getContext('2d');
    const baseUrl = <?= json_encode(rtrim(View::url(''), '/')) ?>;
    let scanning = true;

    function setStatus(message, icon = 'bi-camera-video') {
        statusEl.innerHTML = `<i class="bi ${icon} me-1"></i> ${message}`;
    }

    function openAssetFromText(text) {
        const value = String(text || '').trim();
        if (!value) return false;

        let id = null;
        try {
            const url = new URL(value, window.location.origin);
            const assetMatch = url.pathname.match(/\/qr\/asset\/(\d+)/);
            const tagMatch = url.pathname.match(/\/qr\/asset-tag\/([^/]+)/);
            if (assetMatch) id = assetMatch[1];
            if (tagMatch) {
                scanning = false;
                setStatus('Asset tag found. Opening details...', 'bi-check-circle');
                window.location.href = `${baseUrl}/qr/asset-tag/${encodeURIComponent(decodeURIComponent(tagMatch[1]))}`;
                return true;
            }
        } catch (e) {}

        if (!id && /^\d+$/.test(value)) id = value;
        if (id) {
            scanning = false;
            setStatus('Asset found. Opening details...', 'bi-check-circle');
            window.location.href = `${baseUrl}/qr/asset/${id}`;
            return true;
        }

        setStatus('QR found, but it is not a HEMS asset label.', 'bi-exclamation-triangle');
        return false;
    }

    function scanFrame() {
        if (!scanning) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code && openAssetFromText(code.data)) return;
        }
        requestAnimationFrame(scanFrame);
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Camera API is unavailable. Use manual entry below.', 'bi-exclamation-circle');
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });
            video.srcObject = stream;
            await video.play();
            setStatus('Scanning for asset QR code...', 'bi-qr-code-scan');
            requestAnimationFrame(scanFrame);
        } catch (error) {
            setStatus('Camera access was blocked. Use manual entry below.', 'bi-camera-video-off');
        }
    }

    document.getElementById('manualGo').addEventListener('click', function() {
        const value = document.getElementById('manualAsset').value.trim();
        if (/^\d+$/.test(value)) {
            window.location.href = `${baseUrl}/qr/asset/${value}`;
        } else if (value) {
            window.location.href = `${baseUrl}/qr/asset-tag/${encodeURIComponent(value)}`;
        } else {
            setStatus('Enter an asset ID or tag first.', 'bi-keyboard');
        }
    });

    document.getElementById('manualAsset').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') document.getElementById('manualGo').click();
    });

    startCamera();
})();
</script>
</body>
</html>
