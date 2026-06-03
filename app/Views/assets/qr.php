<?php use App\Helpers\View; $qrUrl = View::url('qr/asset/' . $asset->id); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Lookup - HEMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: #ffffff;
            padding: 2.5rem 1.5rem;
            text-align: center;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }
        .content {
            padding: 2rem 1.5rem;
            flex: 1;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #64748b;
        }
        .detail-value {
            font-weight: 600;
            color: #0f172a;
        }
        .action-button {
            background-color: #dc2626;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.85rem;
            border-radius: 12px;
            width: 100%;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            border: none;
            transition: background-color 0.2s ease;
        }
        .action-button:hover {
            background-color: #b91c1c;
            color: #ffffff;
        }
        
        /* Print Label Styles */
        @media print {
            body {
                background-color: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .print-label {
                width: 3.5in;
                height: 2.0in;
                padding: 0.2in;
                border: 1px dashed #000;
                margin: 0 auto;
                box-sizing: border-box;
                page-break-inside: avoid;
                font-family: monospace;
            }
        }
        .print-label-view {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #e2e8f0;
        }
        .label-card {
            width: 3.5in;
            height: 2.0in;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 0.2in;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-sizing: border-box;
            border: 1px solid #94a3b8;
        }
    </style>
</head>
<body>

<?php if ($printOnly): ?>
    <!-- Print Label Template (Shows Label Card with Print Actions) -->
    <div class="print-label-view">
        <div class="d-flex flex-column align-items-center gap-3">
            <div class="label-card" id="labelCard">
                <div class="d-flex flex-column justify-content-between h-100" style="width: 60%; font-family: monospace;">
                    <div>
                        <div style="font-weight: 800; font-size: 14px; text-transform: uppercase;">HealthCentral</div>
                        <div style="font-size: 8px; color: #64748b;">Asset Tag ID Label</div>
                    </div>
                    <div style="font-size: 10px; margin-top: 10px;">
                        <strong>TAG:</strong> <?= htmlspecialchars($asset->asset_tag) ?><br>
                        <strong>NAME:</strong> <?= htmlspecialchars(substr($asset->name, 0, 18)) ?><br>
                        <strong>SN:</strong> <?= htmlspecialchars(substr($asset->serial_number ?? '-', 0, 16)) ?>
                    </div>
                    <div style="font-size: 8px; color: #94a3b8; margin-top: 5px;">Scan to report issue</div>
                </div>
                <div id="qrcodePrint" style="width: 1.1in; height: 1.1in; flex-shrink: 0;"></div>
            </div>
            
            <div class="no-print d-flex gap-2">
                <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer me-1"></i>Print Label</button>
                <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Close Window</button>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Mobile-First Public Lookup & Reporting View -->
    <div class="mobile-container">
        <div class="header">
            <i class="bi bi-hospital fs-1 mb-2"></i>
            <h4 class="fw-bold mb-0">HealthCentral HEMS</h4>
            <small class="opacity-75">Clinical Asset Registry Quick-Scan</small>
        </div>
        <div class="content">
            <div class="card border-0 bg-light p-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded p-2"><i class="bi bi-hdd-stack fs-3"></i></div>
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($asset->name) ?></h5>
                        <span class="badge bg-primary mt-1"><?= htmlspecialchars($asset->asset_tag) ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold border-bottom pb-2 mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Specification details:</h6>
                <div class="detail-item"><span class="detail-label">Status</span><span class="detail-value text-uppercase"><?= htmlspecialchars($asset->status) ?></span></div>
                <div class="detail-item"><span class="detail-label">Manufacturer</span><span class="detail-value"><?= htmlspecialchars($asset->manufacturer ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Model</span><span class="detail-value"><?= htmlspecialchars($asset->model ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Category</span><span class="detail-value"><?= htmlspecialchars($asset->category_name ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Serial Number</span><span class="detail-value text-monospace"><?= htmlspecialchars($asset->serial_number ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Department</span><span class="detail-value"><?= htmlspecialchars($asset->department_name ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Building Location</span><span class="detail-value"><?= htmlspecialchars($asset->building_name ?? '-') ?></span></div>
            </div>

            <div class="d-flex flex-column gap-2 mt-5">
                <a href="<?= View::url('tickets/create?asset_id=' . $asset->id) ?>" class="action-button"><i class="bi bi-exclamation-octagon me-2"></i>Report Incident</a>
                <a href="<?= View::url('/') ?>" class="btn btn-outline-secondary py-2 fw-medium rounded-pill"><i class="bi bi-house-door me-1"></i>Employee Portal</a>
            </div>
        </div>
        <footer class="text-center py-3 border-top mt-auto text-muted small bg-light">
            HealthCentral HEMS Core. All rights reserved.
        </footer>
    </div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    $(document).ready(function() {
        const qrUrl = <?= json_encode($qrUrl) ?>;
        
        <?php if ($printOnly): ?>
            // Generate print label QR
            new QRCode(document.getElementById("qrcodePrint"), {
                text: qrUrl,
                width: 100,
                height: 100,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            // Auto open print dialog
            setTimeout(() => { window.print(); }, 500);
        <?php endif; ?>
    });
</script>
</body>
</html>
