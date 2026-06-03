<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Forgot Password | HEMS Core</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;min-height:100vh;background:#f0f2f5;display:flex;align-items:center;justify-content:center}.card-auth{max-width:440px;width:100%;background:#fff;border-radius:16px;padding:2.5rem;box-shadow:0 4px 24px rgba(0,0,0,.06)}.brand-icon{width:40px;height:40px;background:#1a56db;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}.form-control{border-radius:10px;padding:.75rem 1rem;border:1.5px solid #e2e8f0}.form-control:focus{border-color:#1a56db;box-shadow:0 0 0 3px rgba(26,86,219,.1)}.btn-primary{background:#1a56db;border:none;border-radius:10px;padding:.8rem;font-weight:600}.btn-primary:hover{background:#1e40af}</style>
</head><body>
<div class="card-auth">
    <div class="text-center mb-4"><div class="brand-icon mx-auto mb-3"><i class="bi bi-shield-check"></i></div>
    <h2 class="fw-bold">Forgot Password</h2><p class="text-muted">Enter your email to receive a reset link.</p></div>
    <?php if(!empty($flashSuccess)):?><div class="alert alert-success"><?=htmlspecialchars($flashSuccess)?></div><?php endif;?>
    <?php if(!empty($flashError)):?><div class="alert alert-danger"><?=htmlspecialchars($flashError)?></div><?php endif;?>
    <form method="POST" action="<?=\App\Helpers\View::url('forgot-password')?>">
        <?=\App\Helpers\CSRF::field()?>
        <div class="mb-3"><label class="form-label fw-medium">Work Email</label>
        <input type="email" name="email" class="form-control" placeholder="name@healthcentral.org" required></div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    </form>
    <div class="text-center mt-3"><a href="<?=\App\Helpers\View::url('login')?>" class="text-decoration-none" style="color:#1a56db"><i class="bi bi-arrow-left me-1"></i>Back to Login</a></div>
</div></body></html>
