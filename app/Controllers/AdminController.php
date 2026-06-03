<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class AdminController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Ensure only managers/admins access this controller (except super admin)
        $role = Session::get('role');
        if (!in_array($role, ['manager', 'administrator', 'super_administrator'])) {
            $this->abort(403, 'Unauthorized administrative access.');
        }
    }

    public function users(): void
    {
        $search = $_GET['search'] ?? '';
        $sql = "SELECT u.*, d.name as dept_name, r.name as role_name 
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.deleted_at IS NULL";
        $params = [];
        if ($search) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.job_title LIKE ?)";
            $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%";
        }
        $sql .= " ORDER BY u.created_at DESC";
        
        $users = $this->db->fetchAll($sql, $params);
        $this->view('admin/users', [
            'pageTitle' => 'User Management',
            'users' => $users,
            'search' => $search
        ]);
    }

    public function createUser(): void
    {
        $roles = $this->db->fetchAll("SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY name");
        $departments = $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");
        
        $this->view('admin/create_user', [
            'pageTitle' => 'Create New User',
            'roles' => $roles,
            'departments' => $departments
        ]);
    }

    public function storeUser(): void
    {
        $v = new Validator($_POST);
        $v->required('first_name')->required('last_name')->required('email')->required('password')->required('role_id');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/admin/users/create');
        }

        // Email unique check
        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [trim($_POST['email'])]);
        if ($exists) {
            Session::flash('error', 'Email address is already in use.');
            $this->redirect('/admin/users/create');
        }

        $userId = $this->db->insert('users', [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'password' => password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => trim($_POST['phone'] ?? ''),
            'department_id' => $_POST['department_id'] ?: null,
            'job_title' => trim($_POST['job_title'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);

        $this->db->insert('user_roles', [
            'user_id' => $userId,
            'role_id' => (int)$_POST['role_id']
        ]);

        // Audit log
        $this->db->insert('audit_logs', [
            'user_id' => Session::userId(),
            'action' => 'create',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'new_values' => json_encode(['email' => $_POST['email']]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        Session::flash('success', 'User account created successfully.');
        $this->redirect('/admin/users');
    }

    public function editUser(string $id): void
    {
        $user = $this->db->fetch(
            "SELECT u.*, ur.role_id 
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             WHERE u.id = ? AND u.deleted_at IS NULL",
            [(int)$id]
        );
        if (!$user) $this->abort(404);

        $roles = $this->db->fetchAll("SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY name");
        $departments = $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('admin/edit_user', [
            'pageTitle' => 'Edit User: ' . $user->first_name,
            'user' => $user,
            'roles' => $roles,
            'departments' => $departments
        ]);
    }

    public function updateUser(string $id): void
    {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$user) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('first_name')->required('last_name')->required('email')->required('role_id');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/admin/users/' . $id . '/edit');
        }

        // Email unique check
        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL", [trim($_POST['email']), (int)$id]);
        if ($exists) {
            Session::flash('error', 'Email address is already in use by another user.');
            $this->redirect('/admin/users/' . $id . '/edit');
        }

        $data = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'department_id' => $_POST['department_id'] ?: null,
            'job_title' => trim($_POST['job_title'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            // Reset throttle locks if password changed manually by admin
            $data['login_attempts'] = 0;
            $data['locked_until'] = null;
        }

        $this->db->update('users', $data, 'id = ?', [(int)$id]);

        // Assign role
        $this->db->query("DELETE FROM user_roles WHERE user_id = ?", [(int)$id]);
        $this->db->insert('user_roles', [
            'user_id' => (int)$id,
            'role_id' => (int)$_POST['role_id']
        ]);

        // Audit log
        $this->db->insert('audit_logs', [
            'user_id' => Session::userId(),
            'action' => 'update',
            'entity_type' => 'user',
            'entity_id' => (int)$id,
            'new_values' => json_encode(['email' => $_POST['email']]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        Session::flash('success', 'User account updated.');
        $this->redirect('/admin/users');
    }

    public function roles(): void
    {
        $roles = $this->db->fetchAll("SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY name");
        $this->view('admin/roles', [
            'pageTitle' => 'RBAC Roles Matrix',
            'roles' => $roles
        ]);
    }

    public function editRole(string $id): void
    {
        $roleObj = $this->db->fetch("SELECT * FROM roles WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$roleObj) $this->abort(404);

        $permissions = $this->db->fetchAll("SELECT * FROM permissions ORDER BY module ASC, name ASC");
        
        $rolePermRows = $this->db->fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$roleObj->id]);
        $rolePerms = array_map(fn($rp) => (int)$rp->permission_id, $rolePermRows);

        $this->view('admin/edit_role', [
            'pageTitle' => 'Edit Permissions Matrix: ' . $roleObj->name,
            'role' => $roleObj,
            'permissions' => $permissions,
            'rolePerms' => $rolePerms
        ]);
    }

    public function updateRole(string $id): void
    {
        $roleObj = $this->db->fetch("SELECT * FROM roles WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$roleObj) $this->abort(404);

        $submittedPerms = $_POST['permissions'] ?? []; // Array of permission IDs

        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleObj->id]);
            foreach ($submittedPerms as $pId) {
                $this->db->insert('role_permissions', [
                    'role_id' => $roleObj->id,
                    'permission_id' => (int)$pId
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            Session::flash('error', 'Failed to update role matrix: ' . $e->getMessage());
            $this->redirect('/admin/roles/' . $id . '/edit');
        }

        // Audit Log
        $this->db->insert('audit_logs', [
            'user_id' => Session::userId(),
            'action' => 'update_permissions',
            'entity_type' => 'role',
            'entity_id' => $roleObj->id,
            'new_values' => json_encode($submittedPerms),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        Session::flash('success', 'Role permission matrix updated successfully.');
        $this->redirect('/admin/roles');
    }

    public function departments(): void
    {
        $departments = $this->db->fetchAll(
            "SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as head_name 
             FROM departments d
             LEFT JOIN users u ON d.head_user_id = u.id
             WHERE d.deleted_at IS NULL ORDER BY d.name"
        );

        $buildings = $this->db->fetchAll("SELECT * FROM buildings WHERE deleted_at IS NULL ORDER BY name");

        $this->view('admin/departments', [
            'pageTitle' => 'Departments & Facility Structure',
            'departments' => $departments,
            'buildings' => $buildings
        ]);
    }

    public function createDepartment(): void
    {
        $users = $this->db->fetchAll("SELECT id, first_name, last_name FROM users WHERE status='active' AND deleted_at IS NULL ORDER BY first_name");
        $this->view('admin/create_department', [
            'pageTitle' => 'Create Department',
            'users' => $users
        ]);
    }

    public function storeDepartment(): void
    {
        $v = new Validator($_POST);
        $v->required('name')->required('code');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/admin/departments/create');
        }

        $code = trim(strtoupper($_POST['code']));
        
        $exists = $this->db->fetch("SELECT id FROM departments WHERE code = ? AND deleted_at IS NULL", [$code]);
        if ($exists) {
            Session::flash('error', 'Department code already registered.');
            $this->redirect('/admin/departments/create');
        }

        $this->db->insert('departments', [
            'name' => trim($_POST['name']),
            'code' => $code,
            'description' => trim($_POST['description'] ?? ''),
            'head_user_id' => $_POST['head_user_id'] ?: null,
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'is_active' => 1
        ]);

        Session::flash('success', 'Department created successfully.');
        $this->redirect('/admin/departments');
    }

    public function settings(): void
    {
        $this->view('admin/settings', [
            'pageTitle' => 'Branding & SLA Settings'
        ]);
    }

    public function updateSettings(): void
    {
        $v = new Validator($_POST);
        $v->required('name')->required('url');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/admin/settings');
        }

        $appConfig = $GLOBALS['appConfig'];
        $appConfig['name'] = trim($_POST['name']);
        $appConfig['url'] = trim($_POST['url']);
        
        // Throttles
        $appConfig['login_throttle']['max_attempts'] = (int)($_POST['max_attempts'] ?? 5);
        $appConfig['login_throttle']['lockout_time'] = (int)($_POST['lockout_time'] ?? 900);

        // SLA
        $appConfig['sla_defaults']['critical'] = (int)($_POST['sla_critical'] ?? 60);
        $appConfig['sla_defaults']['high'] = (int)($_POST['sla_high'] ?? 240);
        $appConfig['sla_defaults']['medium'] = (int)($_POST['sla_medium'] ?? 480);
        $appConfig['sla_defaults']['low'] = (int)($_POST['sla_low'] ?? 1440);

        $content = "<?php\n/**\n * Application Configuration\n * Updated via Settings Admin\n */\n\nreturn " . var_export($appConfig, true) . ";\n";
        
        if (file_put_contents(CONFIG_PATH . '/app.php', $content) === false) {
            Session::flash('error', 'Failed to update config/app.php file. Check permissions.');
        } else {
            Session::flash('success', 'System configuration updated successfully.');
        }

        $this->redirect('/admin/settings');
    }

    public function auditLogs(): void
    {
        $logs = $this->db->fetchAll(
            "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC LIMIT 100"
        );

        $this->view('admin/audit_logs', [
            'pageTitle' => 'System Audit Trail',
            'logs' => $logs
        ]);
    }
}
