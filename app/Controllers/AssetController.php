<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class AssetController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $category = $_GET['category_id'] ?? '';
        $dept = $_GET['department_id'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT a.*, ac.name as category_name, d.name as department_name 
                FROM assets a
                LEFT JOIN asset_categories ac ON a.category_id = ac.id
                LEFT JOIN departments d ON a.department_id = d.id
                WHERE a.deleted_at IS NULL";
        
        $params = [];
        if ($status) { $sql .= " AND a.status = ?"; $params[] = $status; }
        if ($category) { $sql .= " AND a.category_id = ?"; $params[] = $category; }
        if ($dept) { $sql .= " AND a.department_id = ?"; $params[] = $dept; }
        if ($search) {
            $sql .= " AND (a.name LIKE ? OR a.asset_tag LIKE ? OR a.serial_number LIKE ?)";
            $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY a.asset_tag ASC";
        $assets = $this->db->fetchAll($sql, $params);

        $categories = $this->db->fetchAll("SELECT * FROM asset_categories WHERE deleted_at IS NULL ORDER BY name");
        $departments = $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('assets/index', [
            'pageTitle' => 'Asset Registry',
            'assets' => $assets,
            'categories' => $categories,
            'departments' => $departments,
            'filters' => ['status' => $status, 'category_id' => $category, 'department_id' => $dept, 'search' => $search]
        ]);
    }

    public function create(): void
    {
        $categories = $this->db->fetchAll("SELECT * FROM asset_categories WHERE deleted_at IS NULL ORDER BY name");
        $departments = $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $buildings = $this->db->fetchAll("SELECT * FROM buildings WHERE deleted_at IS NULL ORDER BY name");
        
        $this->view('assets/create', [
            'pageTitle' => 'Register Asset',
            'categories' => $categories,
            'departments' => $departments,
            'buildings' => $buildings
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('asset_tag')->required('name')->required('status');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/assets/create');
        }

        // Verify asset tag uniqueness
        $exists = $this->db->fetch("SELECT id FROM assets WHERE asset_tag = ? AND deleted_at IS NULL", [trim($_POST['asset_tag'])]);
        if ($exists) {
            Session::flash('error', 'Asset tag already registered.');
            $this->redirect('/assets/create');
        }

        $data = [
            'asset_tag' => trim($_POST['asset_tag']),
            'name' => trim($_POST['name']),
            'serial_number' => trim($_POST['serial_number'] ?? ''),
            'category_id' => $_POST['category_id'] ?: null,
            'manufacturer' => trim($_POST['manufacturer'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'department_id' => $_POST['department_id'] ?: null,
            'building_id' => $_POST['building_id'] ?: null,
            'floor_id' => $_POST['floor_id'] ?: null,
            'room_id' => $_POST['room_id'] ?: null,
            'status' => $_POST['status'] ?? 'active',
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'purchase_cost' => $_POST['purchase_cost'] ?: null,
            'warranty_expiry' => $_POST['warranty_expiry'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $id = $this->db->insert('assets', $data);

        // Record history
        $this->db->insert('asset_history', [
            'asset_id' => $id,
            'user_id' => Session::userId(),
            'action' => 'registered',
            'description' => 'Asset registered in system'
        ]);

        Session::flash('success', 'Asset registered successfully.');
        $this->redirect('/assets/' . $id);
    }

    public function show(string $id): void
    {
        $asset = $this->db->fetch(
            "SELECT a.*, ac.name as category_name, d.name as department_name, b.name as building_name
             FROM assets a
             LEFT JOIN asset_categories ac ON a.category_id = ac.id
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN buildings b ON a.building_id = b.id
             WHERE a.id = ? AND a.deleted_at IS NULL",
            [(int)$id]
        );

        if (!$asset) $this->abort(404);

        $assignments = $this->db->fetchAll(
            "SELECT aa.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email as user_email
             FROM asset_assignments aa
             JOIN users u ON aa.user_id = u.id
             WHERE aa.asset_id = ? ORDER BY aa.assigned_at DESC",
            [(int)$id]
        );

        $history = $this->db->fetchAll(
            "SELECT ah.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM asset_history ah
             JOIN users u ON ah.user_id = u.id
             WHERE ah.asset_id = ? ORDER BY ah.created_at DESC",
            [(int)$id]
        );

        $tickets = $this->db->fetchAll(
            "SELECT t.id, t.ticket_number, t.title, t.status, t.priority, t.created_at
             FROM tickets t
             WHERE t.asset_id = ? AND t.deleted_at IS NULL ORDER BY t.created_at DESC",
            [(int)$id]
        );

        $maintenance = $this->db->fetchAll(
            "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) as tech_name
             FROM maintenance_tasks m
             LEFT JOIN users u ON m.assigned_to = u.id
             WHERE m.asset_id = ? AND m.deleted_at IS NULL ORDER BY m.scheduled_date DESC",
            [(int)$id]
        );

        $users = $this->db->fetchAll("SELECT id, first_name, last_name FROM users WHERE status='active' AND deleted_at IS NULL ORDER BY first_name");

        $this->view('assets/show', [
            'pageTitle' => 'Asset: ' . $asset->asset_tag,
            'asset' => $asset,
            'assignments' => $assignments,
            'history' => $history,
            'tickets' => $tickets,
            'maintenance' => $maintenance,
            'users' => $users
        ]);
    }

    public function edit(string $id): void
    {
        $asset = $this->db->fetch("SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$asset) $this->abort(404);

        $categories = $this->db->fetchAll("SELECT * FROM asset_categories WHERE deleted_at IS NULL ORDER BY name");
        $departments = $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $buildings = $this->db->fetchAll("SELECT * FROM buildings WHERE deleted_at IS NULL ORDER BY name");

        $this->view('assets/edit', [
            'pageTitle' => 'Edit Asset ' . $asset->asset_tag,
            'asset' => $asset,
            'categories' => $categories,
            'departments' => $departments,
            'buildings' => $buildings
        ]);
    }

    public function update(string $id): void
    {
        $asset = $this->db->fetch("SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$asset) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('asset_tag')->required('name')->required('status');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/assets/' . $id . '/edit');
        }

        // Verify asset tag uniqueness excluding self
        $exists = $this->db->fetch("SELECT id FROM assets WHERE asset_tag = ? AND id != ? AND deleted_at IS NULL", [trim($_POST['asset_tag']), (int)$id]);
        if ($exists) {
            Session::flash('error', 'Asset tag already registered to another asset.');
            $this->redirect('/assets/' . $id . '/edit');
        }

        $data = [
            'asset_tag' => trim($_POST['asset_tag']),
            'name' => trim($_POST['name']),
            'serial_number' => trim($_POST['serial_number'] ?? ''),
            'category_id' => $_POST['category_id'] ?: null,
            'manufacturer' => trim($_POST['manufacturer'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'department_id' => $_POST['department_id'] ?: null,
            'building_id' => $_POST['building_id'] ?: null,
            'floor_id' => $_POST['floor_id'] ?: null,
            'room_id' => $_POST['room_id'] ?: null,
            'status' => $_POST['status'] ?? 'active',
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'purchase_cost' => $_POST['purchase_cost'] ?: null,
            'warranty_expiry' => $_POST['warranty_expiry'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $this->db->update('assets', $data, 'id = ?', [(int)$id]);

        $this->db->insert('asset_history', [
            'asset_id' => (int)$id,
            'user_id' => Session::userId(),
            'action' => 'updated',
            'description' => 'Asset details updated'
        ]);

        Session::flash('success', 'Asset details updated.');
        $this->redirect('/assets/' . $id);
    }

    public function assignAsset(string $id): void
    {
        $asset = $this->db->fetch("SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$asset) $this->abort(404);

        $userId = (int)($_POST['user_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (!$userId) {
            Session::flash('error', 'Please select a user to assign.');
            $this->redirect('/assets/' . $id);
        }

        // Return current active assignments
        $this->db->update('asset_assignments', [
            'returned_at' => date('Y-m-d H:i:s')
        ], 'asset_id = ? AND returned_at IS NULL', [(int)$id]);

        // Insert new assignment
        $this->db->insert('asset_assignments', [
            'asset_id' => (int)$id,
            'user_id' => $userId,
            'assigned_by' => Session::userId(),
            'notes' => $notes
        ]);

        $user = $this->db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);

        $this->db->insert('asset_history', [
            'asset_id' => (int)$id,
            'user_id' => Session::userId(),
            'action' => 'assigned',
            'description' => 'Asset assigned to ' . $user->first_name . ' ' . $user->last_name . '. Notes: ' . $notes
        ]);

        Session::flash('success', 'Asset assigned successfully.');
        $this->redirect('/assets/' . $id);
    }

    public function generateQR(string $id): void
    {
        $asset = $this->db->fetch("SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$asset) $this->abort(404);

        $this->view('assets/qr', [
            'pageTitle' => 'Print Asset Label: ' . $asset->asset_tag,
            'asset' => $asset,
            'printOnly' => true
        ], null);
    }

    public function qrView(string $id): void
    {
        $asset = $this->db->fetch(
            "SELECT a.*, ac.name as category_name, d.name as department_name, b.name as building_name
             FROM assets a
             LEFT JOIN asset_categories ac ON a.category_id = ac.id
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN buildings b ON a.building_id = b.id
             WHERE a.id = ? AND a.deleted_at IS NULL",
            [(int)$id]
        );

        if (!$asset) $this->abort(404);

        $this->view('assets/qr', [
            'pageTitle' => 'Asset Scan: ' . $asset->asset_tag,
            'asset' => $asset,
            'printOnly' => false
        ], null);
    }

    public function dataList(): void
    {
        $assets = $this->db->fetchAll("SELECT id, name, asset_tag, serial_number, status FROM assets WHERE deleted_at IS NULL");
        $this->json(['data' => $assets]);
    }
}
