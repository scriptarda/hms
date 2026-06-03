<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class MaintenanceController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $priority = $_GET['priority'] ?? '';

        $sql = "SELECT m.*, a.name as asset_name, a.asset_tag,
                       CONCAT(u.first_name, ' ', u.last_name) as tech_name
                FROM maintenance_tasks m
                LEFT JOIN assets a ON m.asset_id = a.id
                LEFT JOIN users u ON m.assigned_to = u.id
                WHERE m.deleted_at IS NULL";
        
        $params = [];
        if ($status) { $sql .= " AND m.status = ?"; $params[] = $status; }
        if ($type) { $sql .= " AND m.type = ?"; $params[] = $type; }
        if ($priority) { $sql .= " AND m.priority = ?"; $params[] = $priority; }

        $sql .= " ORDER BY m.due_date ASC";
        $tasks = $this->db->fetchAll($sql, $params);

        $this->view('maintenance/index', [
            'pageTitle' => 'Maintenance Work Orders',
            'tasks' => $tasks,
            'filters' => ['status' => $status, 'type' => $type, 'priority' => $priority]
        ]);
    }

    public function calendar(): void
    {
        $this->view('maintenance/calendar', [
            'pageTitle' => 'Maintenance Schedule Calendar'
        ]);
    }

    public function create(): void
    {
        $assets = $this->db->fetchAll("SELECT id, asset_tag, name FROM assets WHERE deleted_at IS NULL ORDER BY asset_tag");
        $technicians = $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician', 'biomedical_engineer', 'administrator', 'super_administrator')
             AND u.status = 'active' AND u.deleted_at IS NULL"
        );
        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('maintenance/create', [
            'pageTitle' => 'Schedule Maintenance',
            'assets' => $assets,
            'technicians' => $technicians,
            'departments' => $departments
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('title')->required('type')->required('priority')->required('scheduled_date')->required('due_date');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/maintenance/create');
        }

        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'asset_id' => $_POST['asset_id'] ?: null,
            'type' => $_POST['type'],
            'priority' => $_POST['priority'],
            'status' => 'scheduled',
            'assigned_to' => $_POST['assigned_to'] ?: null,
            'department_id' => $_POST['department_id'] ?: null,
            'scheduled_date' => $_POST['scheduled_date'],
            'due_date' => $_POST['due_date'],
            'estimated_hours' => $_POST['estimated_hours'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $id = $this->db->insert('maintenance_tasks', $data);

        // Add log
        $this->db->insert('maintenance_logs', [
            'task_id' => $id,
            'user_id' => Session::userId(),
            'action' => 'scheduled',
            'notes' => 'Work order scheduled'
        ]);

        if ($data['asset_id']) {
            $this->db->insert('asset_history', [
                'asset_id' => $data['asset_id'],
                'user_id' => Session::userId(),
                'action' => 'maintenance_scheduled',
                'description' => 'Preventive maintenance scheduled: ' . $data['title']
            ]);
        }

        Session::flash('success', 'Maintenance scheduled successfully.');
        $this->redirect('/maintenance/' . $id);
    }

    public function show(string $id): void
    {
        $task = $this->db->fetch(
            "SELECT m.*, a.name as asset_name, a.asset_tag, d.name as dept_name,
                    CONCAT(u.first_name, ' ', u.last_name) as tech_name, u.email as tech_email
             FROM maintenance_tasks m
             LEFT JOIN assets a ON m.asset_id = a.id
             LEFT JOIN departments d ON m.department_id = d.id
             LEFT JOIN users u ON m.assigned_to = u.id
             WHERE m.id = ? AND m.deleted_at IS NULL",
            [(int)$id]
        );

        if (!$task) $this->abort(404);

        $logs = $this->db->fetchAll(
            "SELECT ml.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM maintenance_logs ml
             JOIN users u ON ml.user_id = u.id
             WHERE ml.task_id = ? ORDER BY ml.created_at DESC",
            [(int)$id]
        );

        $this->view('maintenance/show', [
            'pageTitle' => 'Work Order #' . $task->id,
            'task' => $task,
            'logs' => $logs
        ]);
    }

    public function edit(string $id): void
    {
        $task = $this->db->fetch("SELECT * FROM maintenance_tasks WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$task) $this->abort(404);

        $assets = $this->db->fetchAll("SELECT id, asset_tag, name FROM assets WHERE deleted_at IS NULL ORDER BY asset_tag");
        $technicians = $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician', 'biomedical_engineer', 'administrator', 'super_administrator')
             AND u.status = 'active' AND u.deleted_at IS NULL"
        );
        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('maintenance/edit', [
            'pageTitle' => 'Edit Maintenance Work Order',
            'task' => $task,
            'assets' => $assets,
            'technicians' => $technicians,
            'departments' => $departments
        ]);
    }

    public function update(string $id): void
    {
        $task = $this->db->fetch("SELECT * FROM maintenance_tasks WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$task) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('title')->required('type')->required('priority')->required('scheduled_date')->required('due_date')->required('status');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/maintenance/' . $id . '/edit');
        }

        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'asset_id' => $_POST['asset_id'] ?: null,
            'type' => $_POST['type'],
            'priority' => $_POST['priority'],
            'status' => $_POST['status'],
            'assigned_to' => $_POST['assigned_to'] ?: null,
            'department_id' => $_POST['department_id'] ?: null,
            'scheduled_date' => $_POST['scheduled_date'],
            'due_date' => $_POST['due_date'],
            'estimated_hours' => $_POST['estimated_hours'] ?: null,
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $this->db->update('maintenance_tasks', $data, 'id = ?', [(int)$id]);

        $this->db->insert('maintenance_logs', [
            'task_id' => (int)$id,
            'user_id' => Session::userId(),
            'action' => 'updated',
            'notes' => 'Work order parameters updated'
        ]);

        Session::flash('success', 'Maintenance details updated.');
        $this->redirect('/maintenance/' . $id);
    }

    public function complete(string $id): void
    {
        $task = $this->db->fetch("SELECT * FROM maintenance_tasks WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$task) $this->abort(404);

        $v = new Validator($_POST);
        $v->required('actual_hours')->required('notes');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/maintenance/' . $id);
        }

        $notes = trim($_POST['notes']);
        $parts = trim($_POST['parts_used'] ?? '');
        $cost = $_POST['cost'] ?: 0.00;
        $hours = (float)$_POST['actual_hours'];

        $this->db->update('maintenance_tasks', [
            'status' => 'completed',
            'completed_date' => date('Y-m-d'),
            'actual_hours' => $hours,
            'cost' => $cost,
            'notes' => $notes
        ], 'id = ?', [(int)$id]);

        // Insert maintenance log
        $this->db->insert('maintenance_logs', [
            'task_id' => (int)$id,
            'user_id' => Session::userId(),
            'action' => 'completed',
            'notes' => $notes,
            'parts_used' => $parts
        ]);

        if ($task->asset_id) {
            $this->db->update('assets', [
                'last_maintenance_date' => date('Y-m-d'),
                'next_maintenance_date' => date('Y-m-d', strtotime('+3 months')), // Schedule next default
                'status' => 'active' // restore asset status to active
            ], 'id = ?', [$task->asset_id]);

            $this->db->insert('asset_history', [
                'asset_id' => $task->asset_id,
                'user_id' => Session::userId(),
                'action' => 'maintenance_completed',
                'description' => 'Completed task: ' . $task->title . '. Cost: $' . number_format($cost, 2)
            ]);
        }

        Session::flash('success', 'Work order marked as completed.');
        $this->redirect('/maintenance/' . $id);
    }

    public function events(): void
    {
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';

        $sql = "SELECT id, title, scheduled_date as start, status, priority, type
                FROM maintenance_tasks
                WHERE deleted_at IS NULL";
        $params = [];

        if ($start && $end) {
            $sql .= " AND scheduled_date BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime($start));
            $params[] = date('Y-m-d', strtotime($end));
        }

        $tasks = $this->db->fetchAll($sql, $params);

        $events = [];
        foreach ($tasks as $t) {
            $color = '#6366f1'; // Default indigo
            if ($t->status === 'completed') {
                $color = '#10b981'; // Green
            } elseif ($t->priority === 'critical') {
                $color = '#ef4444'; // Red
            } elseif ($t->priority === 'high') {
                $color = '#f59e0b'; // Amber
            }

            $events[] = [
                'id' => $t->id,
                'title' => '[' . strtoupper($t->type) . '] ' . $t->title,
                'start' => $t->start,
                'url' => View::url('maintenance/' . $t->id),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true
            ];
        }

        $this->json($events);
    }
}
