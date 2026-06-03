<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Validator;
use App\Helpers\Database;

class ServiceRequestController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $role = Session::get('role', 'staff');
        $userId = Session::userId();

        $sql = "SELECT sr.*, d.name as dept_name,
                       CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                       CONCAT(appr.first_name, ' ', appr.last_name) as approver_name
                FROM service_requests sr
                LEFT JOIN departments d ON sr.department_id = d.id
                LEFT JOIN users req ON sr.requester_id = req.id
                LEFT JOIN users appr ON sr.approved_by = appr.id
                WHERE sr.deleted_at IS NULL";
        
        $params = [];
        
        // Requesters can only see their own requests; managers/admins see all
        if (!in_array($role, ['manager', 'administrator', 'super_administrator'])) {
            $sql .= " AND sr.requester_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY sr.created_at DESC";
        $requests = $this->db->fetchAll($sql, $params);

        $this->view('service-requests/index', [
            'pageTitle' => 'My Service Requests',
            'requests' => $requests
        ]);
    }

    public function catalog(): void
    {
        $this->view('service-requests/catalog', [
            'pageTitle' => 'Service Catalog'
        ]);
    }

    public function create(string $type): void
    {
        $typeNames = [
            'new_computer' => 'New Computer Request',
            'software_install' => 'Software Installation Request',
            'email_setup' => 'Email Account Setup',
            'access_request' => 'System Access Request'
        ];

        if (!array_key_exists($type, $typeNames)) {
            $this->abort(404);
        }

        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('service-requests/create', [
            'pageTitle' => $typeNames[$type],
            'type' => $type,
            'departments' => $departments
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('title')->required('type')->required('priority');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/service-requests/catalog');
        }

        $type = $_POST['type'];
        $title = trim($_POST['title']);
        $priority = $_POST['priority'];
        $deptId = $_POST['department_id'] ?: null;
        
        // Capture JSON specification of dynamic fields
        $spec = $_POST['spec'] ?? [];
        $description = trim($_POST['description'] ?? '');
        if (!empty($spec)) {
            $descParts = [];
            foreach ($spec as $key => $val) {
                $descParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $val;
            }
            $description .= "\n\n=== Request details ===\n" . implode("\n", $descParts);
        }

        // Generate Request Number
        $lastId = (int)$this->db->fetchColumn("SELECT MAX(id) FROM service_requests");
        $requestNumber = 'SR-' . str_pad($lastId + 5200, 4, '0', STR_PAD_LEFT);

        $data = [
            'request_number' => $requestNumber,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'requester_id' => Session::userId(),
            'department_id' => $deptId,
            'priority' => $priority,
            'status' => 'pending_approval' // Auto submit for approval
        ];

        $id = $this->db->insert('service_requests', $data);

        // Find department manager to assign approval task
        // Fallback to ID 8 (manager) or 1 (super admin)
        $managerId = 8; // Default manager ID from sample seed data
        if ($deptId) {
            $deptHead = $this->db->fetchColumn("SELECT head_user_id FROM departments WHERE id = ?", [$deptId]);
            if ($deptHead) $managerId = $deptHead;
        }

        // Create approval task
        $this->db->insert('service_request_approvals', [
            'request_id' => $id,
            'approver_id' => $managerId,
            'status' => 'pending'
        ]);

        // Send Notification
        $this->db->insert('notifications', [
            'user_id' => $managerId,
            'type' => 'approval_required',
            'title' => 'Service Approval Required',
            'message' => "Request {$requestNumber}: {$title} requires your approval.",
            'link' => '/service-requests/' . $id
        ]);

        Session::flash('success', 'Service request submitted successfully for approval.');
        $this->redirect('/service-requests/' . $id);
    }

    public function show(string $id): void
    {
        $request = $this->db->fetch(
            "SELECT sr.*, d.name as dept_name,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name, req.email as requester_email,
                    CONCAT(appr.first_name, ' ', appr.last_name) as approver_name
             FROM service_requests sr
             LEFT JOIN departments d ON sr.department_id = d.id
             LEFT JOIN users req ON sr.requester_id = req.id
             LEFT JOIN users appr ON sr.approved_by = appr.id
             WHERE sr.id = ? AND sr.deleted_at IS NULL",
            [(int)$id]
        );

        if (!$request) $this->abort(404);

        $approvals = $this->db->fetchAll(
            "SELECT sra.*, CONCAT(u.first_name, ' ', u.last_name) as approver_name
             FROM service_request_approvals sra
             JOIN users u ON sra.approver_id = u.id
             WHERE sra.request_id = ? ORDER BY sra.created_at ASC",
            [(int)$id]
        );

        $this->view('service-requests/show', [
            'pageTitle' => 'Request ' . $request->request_number,
            'request' => $request,
            'approvals' => $approvals
        ]);
    }

    public function approve(string $id): void
    {
        $request = $this->db->fetch("SELECT * FROM service_requests WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$request) $this->abort(404);

        $userId = Session::userId();

        // Check if there is an active pending approval for this user
        $approval = $this->db->fetch(
            "SELECT * FROM service_request_approvals WHERE request_id = ? AND approver_id = ? AND status = 'pending'",
            [$request->id, $userId]
        );

        // Allow Super Admin to override
        $role = Session::get('role');
        if (!$approval && $role !== 'super_administrator' && $role !== 'administrator') {
            Session::flash('error', 'You are not authorized to approve this request.');
            $this->redirect('/service-requests/' . $id);
        }

        $comments = trim($_POST['comments'] ?? '');

        $this->db->beginTransaction();
        try {
            // Update request
            $this->db->update('service_requests', [
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$request->id]);

            // Update approvals task
            if ($approval) {
                $this->db->update('service_request_approvals', [
                    'status' => 'approved',
                    'comments' => $comments,
                    'acted_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$approval->id]);
            } else {
                // Insert a retrospective approval record
                $this->db->insert('service_request_approvals', [
                    'request_id' => $request->id,
                    'approver_id' => $userId,
                    'status' => 'approved',
                    'comments' => $comments . ' (Overridden by Admin)',
                    'acted_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Create an incident ticket if it is a fulfillment request that needs technical processing
            // e.g. software_install, email_setup, etc.
            $ticketCategory = 2; // Software default
            if ($request->type === 'new_computer') {
                $ticketCategory = 1; // Hardware
            } elseif ($request->type === 'access_request') {
                $ticketCategory = 6; // Access & Security
            }

            $lastTicketId = (int)$this->db->fetchColumn("SELECT MAX(id) FROM tickets");
            $ticketNumber = 'HEMS-' . str_pad($lastTicketId + 9400, 4, '0', STR_PAD_LEFT);

            $this->db->insert('tickets', [
                'ticket_number' => $ticketNumber,
                'title' => '[REQ FULFILL] ' . $request->title,
                'description' => "Fulfillment task for Service Request: {$request->request_number}\n\n" . $request->description,
                'category_id' => $ticketCategory,
                'priority' => $request->priority,
                'status' => 'new',
                'requester_id' => $request->requester_id,
                'department_id' => $request->department_id,
                'sla_due_at' => date('Y-m-d H:i:s', time() + 86400 * 2) // 48h SLA
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            Session::flash('error', 'Failed to approve: ' . $e->getMessage());
            $this->redirect('/service-requests/' . $id);
        }

        // Notify requester
        $this->db->insert('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'ticket_updated',
            'title' => 'Service Request Approved',
            'message' => "Your request {$request->request_number} has been approved. A fulfillment ticket has been opened.",
            'link' => '/service-requests/' . $id
        ]);

        Session::flash('success', 'Request approved. Fulfillment task generated.');
        $this->redirect('/service-requests/' . $id);
    }

    public function reject(string $id): void
    {
        $request = $this->db->fetch("SELECT * FROM service_requests WHERE id = ? AND deleted_at IS NULL", [(int)$id]);
        if (!$request) $this->abort(404);

        $userId = Session::userId();
        $approval = $this->db->fetch(
            "SELECT * FROM service_request_approvals WHERE request_id = ? AND approver_id = ? AND status = 'pending'",
            [$request->id, $userId]
        );

        $role = Session::get('role');
        if (!$approval && $role !== 'super_administrator' && $role !== 'administrator') {
            Session::flash('error', 'You are not authorized to reject this request.');
            $this->redirect('/service-requests/' . $id);
        }

        $comments = trim($_POST['comments'] ?? '');

        $this->db->beginTransaction();
        try {
            $this->db->update('service_requests', [
                'status' => 'rejected'
            ], 'id = ?', [$request->id]);

            if ($approval) {
                $this->db->update('service_request_approvals', [
                    'status' => 'rejected',
                    'comments' => $comments,
                    'acted_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$approval->id]);
            } else {
                $this->db->insert('service_request_approvals', [
                    'request_id' => $request->id,
                    'approver_id' => $userId,
                    'status' => 'rejected',
                    'comments' => $comments . ' (Overridden by Admin)',
                    'acted_at' => date('Y-m-d H:i:s')
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            Session::flash('error', 'Failed to reject: ' . $e->getMessage());
            $this->redirect('/service-requests/' . $id);
        }

        // Notify requester
        $this->db->insert('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'ticket_updated',
            'title' => 'Service Request Rejected',
            'message' => "Your request {$request->request_number} has been rejected.",
            'link' => '/service-requests/' . $id
        ]);

        Session::flash('warning', 'Request has been rejected.');
        $this->redirect('/service-requests/' . $id);
    }
}
