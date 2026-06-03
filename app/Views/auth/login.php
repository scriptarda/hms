<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | HEMS Core</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;min-height:100vh;background:#f0f2f5}
        .login-wrapper{display:flex;min-height:100vh}
        .login-left{flex:1;display:flex;align-items:center;justify-content:center;padding:3rem;background:#fff}
        .login-right{flex:1;background:linear-gradient(135deg,#1a3a8a 0%,#1a56db 50%,#2563eb 100%);display:flex;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden}
        .login-right::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="g" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect fill="url(%23g)" width="100" height="100"/></svg>');opacity:.5}
        .login-form{max-width:420px;width:100%}
        .brand-logo{display:flex;align-items:center;gap:.75rem;margin-bottom:2.5rem}
        .brand-icon{width:40px;height:40px;background:#1a56db;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
        .brand-name{font-size:1.4rem;font-weight:700;color:#1a56db}
        .brand-sub{font-size:.7rem;font-weight:600;color:#64748b;letter-spacing:2px;text-transform:uppercase}
        .login-form h1{font-size:2rem;font-weight:800;color:#0f172a;margin-bottom:.5rem}
        .login-form .subtitle{color:#64748b;margin-bottom:2rem}
        .form-label{font-weight:500;font-size:.875rem;color:#334155}
        .form-control{border-radius:10px;padding:.75rem 1rem;border:1.5px solid #e2e8f0;font-size:.9rem;transition:all .2s}
        .form-control:focus{border-color:#1a56db;box-shadow:0 0 0 3px rgba(26,86,219,.1)}
        .input-group .form-control{border-right:0}
        .input-group .input-group-text{background:#fff;border-radius:0 10px 10px 0;border:1.5px solid #e2e8f0;border-left:0;color:#94a3b8}
        .btn-primary{background:#1a56db;border:none;border-radius:10px;padding:.8rem;font-weight:600;font-size:1rem;transition:all .2s}
        .btn-primary:hover{background:#1e40af;transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,86,219,.3)}
        .btn-outline-secondary{border-radius:10px;padding:.75rem;border:1.5px solid #e2e8f0;font-weight:500;color:#475569}
        .btn-outline-secondary:hover{background:#f8fafc;border-color:#cbd5e1}
        .divider{display:flex;align-items:center;gap:1rem;margin:1.5rem 0;color:#94a3b8;font-size:.75rem;letter-spacing:1px}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
        .forgot-link{color:#1a56db;text-decoration:none;font-weight:500;font-size:.875rem}
        .forgot-link:hover{color:#1e40af}
        .hero-content{position:relative;z-index:1;color:#fff;max-width:450px}
        .hero-badge{font-size:.7rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.7);margin-bottom:1rem}
        .hero-content h2{font-size:2.5rem;font-weight:800;line-height:1.2;margin-bottom:1.25rem}
        .hero-content p{font-size:1rem;color:rgba(255,255,255,.8);line-height:1.6;margin-bottom:2rem}
        .hero-features{display:flex;gap:2rem}
        .hero-features .feature{display:flex;align-items:center;gap:.5rem;font-size:.9rem;color:rgba(255,255,255,.9)}
        .status-badge{position:absolute;top:2rem;right:2rem;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);padding:.5rem 1rem;border-radius:20px;color:#fff;font-size:.8rem;display:flex;align-items:center;gap:.5rem;z-index:1}
        .status-dot{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
        .trust-bar{position:absolute;bottom:2rem;left:3rem;display:flex;align-items:center;gap:.75rem;z-index:1;color:rgba(255,255,255,.7);font-size:.85rem}
        .form-check-label{font-size:.875rem;color:#64748b}
        .footer-text{text-align:center;margin-top:1.5rem;font-size:.8rem;color:#94a3b8}
        .footer-text a{color:#1a56db;text-decoration:none}
        .alert{border-radius:10px;font-size:.875rem}
        @media(max-width:992px){.login-right{display:none}.login-wrapper{justify-content:center}}
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-left">
        <div class="login-form">
            <div class="brand-logo">
                <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
                <div><div class="brand-name">HealthCentral</div><div class="brand-sub">Admin Portal</div></div>
            </div>
            <h1>Welcome back</h1>
            <p class="subtitle">Please enter your credentials to access HEMS Core.</p>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($flashError) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= \App\Helpers\View::url('login') ?>">
                <?= \App\Helpers\CSRF::field() ?>
                <div class="mb-3">
                    <label class="form-label">Work Email</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="name@healthcentral.org" required autofocus>
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Password</label>
                        <a href="<?= \App\Helpers\View::url('forgot-password') ?>" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="input-group mt-1">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required id="passwordField">
                        <span class="input-group-text" style="cursor:pointer" onclick="togglePassword()"><i class="bi bi-eye" id="eyeIcon"></i></span>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me for 30 days</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login to HEMS</button>
            </form>
            <div class="footer-text">
                Authorized access only. By logging in, you agree to our <a href="#">Security Protocols</a> and <a href="#">Privacy Policy</a>.
            </div>
        </div>
    </div>
    <div class="login-right">
        <div class="status-badge"><span class="status-dot"></span> System Status: Optimal</div>
        <div class="hero-content">
            <div class="hero-badge">HEMS Core</div>
            <h2>Precision Intelligence for Modern Healthcare.</h2>
            <p>Streamlining hospital asset management and incident reporting through enterprise-grade data orchestration.</p>
            <div class="hero-features">
                <div class="feature"><i class="bi bi-shield-check"></i> HIPAA Compliant</div>
                <div class="feature"><i class="bi bi-lightning-charge"></i> Real-time Analytics</div>
            </div>
        </div>
        <div class="trust-bar">Trusted by 2,000+ clinicians daily</div>
    </div>
</div>
<script>
function togglePassword(){
    const f=document.getElementById('passwordField'),i=document.getElementById('eyeIcon');
    if(f.type==='password'){f.type='text';i.className='bi bi-eye-slash'}else{f.type='password';i.className='bi bi-eye'}
}
</script>
</body>
</html>
