<?php use App\Helpers\View; use App\Helpers\CSRF; use App\Helpers\Session;
$user = $authUser ?? []; $role = $authRole ?? 'staff'; $perms = $authPermissions ?? [];
$currentUrl = $_GET['url'] ?? '';
function isActive($path, $url) { return strpos($url, trim($path, '/')) === 0 ? 'active' : ''; }
function hasPerm($slug, $perms) { return in_array($slug, $perms); }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'HEMS Core') ?> | HealthCentral</title>
    <?= CSRF::meta() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= View::asset('css/app.css') ?>" rel="stylesheet">
    <?= View::section('styles') ?>
</head>
<body>
<div class="app-wrapper" id="appWrapper">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="<?= View::url('/') ?>" class="sidebar-brand">
                <div class="brand-icon"><i class="bi bi-hospital"></i></div>
                <div class="brand-text"><span class="brand-name">HealthCentral</span><span class="brand-sub">Admin Portal</span></div>
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item <?= isActive('dashboard', $currentUrl) ?: (empty($currentUrl) ? 'active' : '') ?>">
                    <a href="<?= View::url('dashboard') ?>"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a></li>
                <li class="nav-item <?= isActive('tickets', $currentUrl) ?>">
                    <a href="<?= View::url('tickets') ?>"><i class="bi bi-exclamation-triangle"></i><span>Incidents</span></a></li>
                <li class="nav-item <?= isActive('service-requests', $currentUrl) ?>">
                    <a href="<?= View::url('service-requests/catalog') ?>"><i class="bi bi-card-checklist"></i><span>Service Requests</span></a></li>
                <li class="nav-item <?= isActive('sla', $currentUrl) ?>">
                    <a href="<?= View::url('sla') ?>"><i class="bi bi-hourglass-split"></i><span>SLA</span></a></li>
                <li class="nav-item <?= isActive('assets', $currentUrl) ?>">
                    <a href="<?= View::url('assets') ?>"><i class="bi bi-hdd-stack"></i><span>Assets</span></a></li>
                <li class="nav-item <?= isActive('maintenance', $currentUrl) ?>">
                    <a href="<?= View::url('maintenance') ?>"><i class="bi bi-wrench-adjustable"></i><span>Maintenance</span></a></li>
                <li class="nav-item <?= isActive('knowledge', $currentUrl) ?>">
                    <a href="<?= View::url('knowledge') ?>"><i class="bi bi-journal-text"></i><span>Knowledge Base</span></a></li>
                <li class="nav-item <?= isActive('inventory', $currentUrl) ?>">
                    <a href="<?= View::url('inventory') ?>"><i class="bi bi-box-seam"></i><span>Inventory</span></a></li>
                <li class="nav-item <?= isActive('reports', $currentUrl) ?>">
                    <a href="<?= View::url('reports') ?>"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a></li>
                <?php if(in_array($role, ['manager','administrator','super_administrator'])): ?>
                <li class="nav-item <?= isActive('admin/departments', $currentUrl) ?>">
                    <a href="<?= View::url('admin/departments') ?>"><i class="bi bi-building"></i><span>Departments</span></a></li>
                <li class="nav-item <?= isActive('admin/users', $currentUrl) ?>">
                    <a href="<?= View::url('admin/users') ?>"><i class="bi bi-people"></i><span>Users</span></a></li>
                <li class="nav-item <?= isActive('admin/settings', $currentUrl) ?>">
                    <a href="<?= View::url('admin/settings') ?>"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= View::url('knowledge') ?>" class="sidebar-footer-link"><i class="bi bi-question-circle"></i><span>Help</span></a>
            <a href="<?= View::url('logout') ?>" class="sidebar-footer-link"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-topnav">
            <div class="topnav-left">
                <button class="btn btn-link sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
                <div class="topnav-search">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search resources..." id="globalSearch">
                </div>
            </div>
            <div class="topnav-center">
                <a href="<?= View::url('dashboard') ?>" class="topnav-link <?= isActive('dashboard', $currentUrl) ?: (empty($currentUrl) ? 'active' : '') ?>">Dashboard</a>
                <a href="<?= View::url('assets') ?>" class="topnav-link <?= isActive('assets', $currentUrl) ?>">Assets</a>
                <a href="<?= View::url('tickets') ?>" class="topnav-link <?= isActive('tickets', $currentUrl) ?>">Tickets</a>
            </div>
            <div class="topnav-right">
                <a href="<?= View::url('tickets/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Ticket</a>
                <div class="topnav-icon-btn position-relative" id="notifBtn" onclick="window.location.href='<?= View::url('notifications') ?>'">
                    <i class="bi bi-bell"></i><span class="notif-badge" id="notifBadge" style="display:none">0</span>
                </div>
                <div class="topnav-icon-btn" onclick="toggleDarkMode()"><i class="bi bi-gear" id="themeIcon"></i></div>
                <div class="dropdown">
                    <button class="topnav-user dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?= htmlspecialchars(strtoupper(substr($user['first_name']??'U',0,1).substr($user['last_name']??'',0,1))) ?></div>
                        <span class="user-name d-none d-md-inline"><?= htmlspecialchars(($user['first_name']??'').' '.($user['last_name']??'')) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><div class="dropdown-header"><strong><?= htmlspecialchars(($user['first_name']??'').' '.($user['last_name']??'')) ?></strong><br><small class="text-muted"><?= htmlspecialchars($user['job_title']??'') ?></small></div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= View::url('profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= View::url('notifications') ?>"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= View::url('logout') ?>"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="app-content">
            <?php if(!empty($flashSuccess)):?>
            <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif;?>
            <?php if(!empty($flashError)):?>
            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif;?>
            <?php if(!empty($flashWarning)):?>
            <div class="alert alert-warning alert-dismissible fade show mx-3 mt-3" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flashWarning) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif;?>

            <?= View::section('content') ?>
        </main>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php if (!empty($appConfig['realtime']['enabled'])): ?>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<?php endif; ?>
<script>
window.BASE_URL = '<?= rtrim(View::url(""), "/") ?>';
window.AUTH_USER_ID = <?= (int)($user['id'] ?? Session::userId() ?? 0) ?>;
window.SOCKET_IO_URL = '<?= !empty($appConfig['realtime']['enabled']) ? htmlspecialchars($appConfig['realtime']['socket_url'] ?? '') : '' ?>';
window.NOTIFICATION_POLL_INTERVAL = <?= (int)($appConfig['notification_poll_interval'] ?? 30000) ?>;
</script>
<script src="<?= View::asset('js/app.js') ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
