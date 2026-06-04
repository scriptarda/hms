<?php
namespace App\Repositories;

use App\Helpers\Database;

class ServiceRequestRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureModuleTables();
        $this->seedCatalogItems();
    }

    public function getCatalogItems(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM service_catalog_items WHERE deleted_at IS NULL";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";

        return array_map([$this, 'hydrateCatalogItem'], $this->db->fetchAll($sql));
    }

    public function findCatalogItem(string $type): ?object
    {
        $row = $this->db->fetch(
            "SELECT * FROM service_catalog_items WHERE type = ? AND is_active = 1 AND deleted_at IS NULL",
            [$type]
        );

        return $row ? $this->hydrateCatalogItem($row) : null;
    }

    public function getRequests(string $role, int $userId, array $filters = []): array
    {
        $sql = "SELECT sr.*, d.name as dept_name, sci.name as catalog_name, sci.icon as catalog_icon,
                       sci.color as catalog_color, sci.category as catalog_category,
                       CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                       CONCAT(appr.first_name, ' ', appr.last_name) as approver_name,
                       sft.status as fulfillment_status, sft.ticket_id,
                       t.ticket_number as fulfillment_ticket_number
                FROM service_requests sr
                LEFT JOIN service_catalog_items sci ON sr.type = sci.type
                LEFT JOIN departments d ON sr.department_id = d.id
                LEFT JOIN users req ON sr.requester_id = req.id
                LEFT JOIN users appr ON sr.approved_by = appr.id
                LEFT JOIN service_request_fulfillment_tasks sft ON sft.request_id = sr.id
                LEFT JOIN tickets t ON sft.ticket_id = t.id
                WHERE sr.deleted_at IS NULL";

        $params = [];

        if (!in_array($role, ['manager', 'administrator', 'super_administrator'], true)) {
            $sql .= " AND sr.requester_id = ?";
            $params[] = $userId;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND sr.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (sr.request_number LIKE ? OR sr.title LIKE ? OR req.first_name LIKE ? OR req.last_name LIKE ?)";
            $needle = '%' . $filters['search'] . '%';
            array_push($params, $needle, $needle, $needle, $needle);
        }

        $sql .= " ORDER BY sr.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function findRequest(int $id): ?object
    {
        return $this->db->fetch(
            "SELECT sr.*, d.name as dept_name, sci.name as catalog_name, sci.icon as catalog_icon,
                    sci.color as catalog_color, sci.category as catalog_category,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                    req.email as requester_email,
                    CONCAT(appr.first_name, ' ', appr.last_name) as approver_name,
                    sft.id as fulfillment_id, sft.status as fulfillment_status,
                    sft.ticket_id, sft.assigned_to, sft.summary as fulfillment_summary,
                    sft.notes as fulfillment_notes, sft.started_at, sft.completed_at as fulfillment_completed_at,
                    t.ticket_number as fulfillment_ticket_number,
                    CONCAT(assignee.first_name, ' ', assignee.last_name) as fulfillment_assignee_name
             FROM service_requests sr
             LEFT JOIN service_catalog_items sci ON sr.type = sci.type
             LEFT JOIN departments d ON sr.department_id = d.id
             LEFT JOIN users req ON sr.requester_id = req.id
             LEFT JOIN users appr ON sr.approved_by = appr.id
             LEFT JOIN service_request_fulfillment_tasks sft ON sft.request_id = sr.id
             LEFT JOIN tickets t ON sft.ticket_id = t.id
             LEFT JOIN users assignee ON sft.assigned_to = assignee.id
             WHERE sr.id = ? AND sr.deleted_at IS NULL",
            [$id]
        );
    }

    public function getApprovals(int $requestId): array
    {
        return $this->db->fetchAll(
            "SELECT sra.*, CONCAT(u.first_name, ' ', u.last_name) as approver_name
             FROM service_request_approvals sra
             JOIN users u ON sra.approver_id = u.id
             WHERE sra.request_id = ? ORDER BY sra.created_at ASC",
            [$requestId]
        );
    }

    public function getFieldValues(int $requestId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM service_request_field_values WHERE request_id = ? ORDER BY sort_order ASC, id ASC",
            [$requestId]
        );
    }

    public function getActivity(int $requestId): array
    {
        return $this->db->fetchAll(
            "SELECT sra.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM service_request_activity sra
             LEFT JOIN users u ON sra.user_id = u.id
             WHERE sra.request_id = ? ORDER BY sra.created_at ASC",
            [$requestId]
        );
    }

    public function createRequest(array $data): int
    {
        return $this->db->insert('service_requests', $data);
    }

    public function updateRequest(int $requestId, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('service_requests', $data, 'id = ?', [$requestId]);
    }

    public function insertFieldValue(array $data): int
    {
        return $this->db->insert('service_request_field_values', $data);
    }

    public function createApproval(int $requestId, int $approverId): int
    {
        return $this->db->insert('service_request_approvals', [
            'request_id' => $requestId,
            'approver_id' => $approverId,
            'status' => 'pending',
        ]);
    }

    public function findPendingApproval(int $requestId, int $userId): ?object
    {
        return $this->db->fetch(
            "SELECT * FROM service_request_approvals WHERE request_id = ? AND approver_id = ? AND status = 'pending'",
            [$requestId, $userId]
        );
    }

    public function updateApproval(int $approvalId, string $status, string $comments): int
    {
        return $this->db->update('service_request_approvals', [
            'status' => $status,
            'comments' => $comments,
            'acted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$approvalId]);
    }

    public function addActivity(int $requestId, ?int $userId, string $action, string $title, string $description = '', array $metadata = []): int
    {
        return $this->db->insert('service_request_activity', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
        ]);
    }

    public function createFulfillmentTask(array $data): int
    {
        return $this->db->insert('service_request_fulfillment_tasks', $data);
    }

    public function updateFulfillmentTask(int $requestId, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('service_request_fulfillment_tasks', $data, 'request_id = ?', [$requestId]);
    }

    public function findFulfillmentTask(int $requestId): ?object
    {
        return $this->db->fetch("SELECT * FROM service_request_fulfillment_tasks WHERE request_id = ?", [$requestId]);
    }

    public function notify(int $userId, string $type, string $title, string $message, string $link): int
    {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    public function resolveApprover(?int $departmentId, string $approvalMode): ?int
    {
        if ($approvalMode === 'none') {
            return null;
        }

        if ($approvalMode === 'department_head' && $departmentId) {
            $headId = $this->db->fetchColumn("SELECT head_user_id FROM departments WHERE id = ?", [$departmentId]);
            if ($headId) {
                return (int)$headId;
            }
        }

        $roles = $approvalMode === 'administrator'
            ? ['administrator', 'super_administrator']
            : ['manager', 'administrator', 'super_administrator'];

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $row = $this->db->fetch(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ({$placeholders}) AND u.status = 'active' AND u.deleted_at IS NULL
             ORDER BY FIELD(r.slug, 'manager', 'administrator', 'super_administrator'), u.id ASC LIMIT 1",
            $roles
        );

        return $row ? (int)$row->id : null;
    }

    public function getFulfillmentUsers(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician','biomedical_engineer','administrator','super_administrator')
             AND u.status = 'active' AND u.deleted_at IS NULL"
        );
    }

    public function generateRequestNumber(): string
    {
        $last = (int)$this->db->fetchColumn("SELECT MAX(id) FROM service_requests");
        return 'SR-' . str_pad($last + 5200, 4, '0', STR_PAD_LEFT);
    }

    public function findTicketCategoryId(array $names, ?int $fallback = null): ?int
    {
        foreach ($names as $name) {
            $id = $this->db->fetchColumn(
                "SELECT id FROM ticket_categories WHERE name LIKE ? AND deleted_at IS NULL ORDER BY id LIMIT 1",
                ['%' . $name . '%']
            );
            if ($id) {
                return (int)$id;
            }
        }

        return $fallback;
    }

    public function createFulfillmentTicket(array $data): int
    {
        return $this->db->insert('tickets', $data);
    }

    public function addTicketHistory(int $ticketId, int $userId, string $action, string $value = ''): void
    {
        $this->db->insert('ticket_history', [
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'action' => $action,
            'new_value' => $value,
        ]);
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollback(): void { $this->db->rollback(); }

    private function hydrateCatalogItem(object $row): object
    {
        $row->schema = json_decode($row->form_schema ?? '[]', true);
        if (!is_array($row->schema)) {
            $row->schema = [];
        }
        return $row;
    }

    private function ensureModuleTables(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS service_catalog_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                short_description VARCHAR(255),
                description TEXT,
                icon VARCHAR(50) DEFAULT 'bi-card-checklist',
                color VARCHAR(20) DEFAULT '#1a56db',
                category VARCHAR(50) DEFAULT 'General',
                default_priority ENUM('critical','high','medium','low') DEFAULT 'medium',
                approval_mode ENUM('department_head','manager','administrator','none') DEFAULT 'department_head',
                fulfillment_category_id BIGINT UNSIGNED NULL,
                sla_hours INT DEFAULT 48,
                form_schema LONGTEXT NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                INDEX idx_catalog_active (is_active, sort_order)
            ) ENGINE=InnoDB"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS service_request_field_values (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id BIGINT UNSIGNED NOT NULL,
                field_key VARCHAR(100) NOT NULL,
                field_label VARCHAR(150) NOT NULL,
                field_type VARCHAR(30) DEFAULT 'text',
                field_value TEXT,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_request_field (request_id, field_key),
                INDEX idx_srfv_request (request_id),
                FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
            ) ENGINE=InnoDB"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS service_request_activity (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                action VARCHAR(60) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                metadata LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sra_request (request_id, created_at),
                FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS service_request_fulfillment_tasks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_id BIGINT UNSIGNED NOT NULL UNIQUE,
                ticket_id BIGINT UNSIGNED NULL,
                assigned_to BIGINT UNSIGNED NULL,
                status ENUM('queued','in_progress','completed','cancelled') DEFAULT 'queued',
                summary VARCHAR(255),
                notes TEXT,
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_srft_status (status),
                FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );
    }

    private function seedCatalogItems(): void
    {
        foreach (self::defaultCatalogItems() as $item) {
            $this->db->query(
                "INSERT INTO service_catalog_items
                 (type, name, short_description, description, icon, color, category, default_priority, approval_mode, fulfillment_category_id, sla_hours, form_schema, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    short_description = VALUES(short_description),
                    description = VALUES(description),
                    icon = VALUES(icon),
                    color = VALUES(color),
                    category = VALUES(category),
                    default_priority = VALUES(default_priority),
                    approval_mode = VALUES(approval_mode),
                    fulfillment_category_id = VALUES(fulfillment_category_id),
                    sla_hours = VALUES(sla_hours),
                    form_schema = VALUES(form_schema),
                    is_active = VALUES(is_active),
                    sort_order = VALUES(sort_order)",
                [
                    $item['type'],
                    $item['name'],
                    $item['short_description'],
                    $item['description'],
                    $item['icon'],
                    $item['color'],
                    $item['category'],
                    $item['default_priority'],
                    $item['approval_mode'],
                    $item['fulfillment_category_id'],
                    $item['sla_hours'],
                    json_encode($item['schema']),
                    $item['sort_order'],
                ]
            );
        }
    }

    public static function defaultCatalogItems(): array
    {
        return [
            [
                'type' => 'new_computer',
                'name' => 'New Computer Request',
                'short_description' => 'Provision a workstation, laptop, clinical terminal, or monitor bundle.',
                'description' => 'Request a new endpoint device with operating profile, accessories, and delivery location.',
                'icon' => 'bi-pc-display',
                'color' => '#1a56db',
                'category' => 'Hardware',
                'default_priority' => 'medium',
                'approval_mode' => 'department_head',
                'fulfillment_category_id' => 1,
                'sla_hours' => 72,
                'sort_order' => 10,
                'schema' => [
                    ['key' => 'device_type', 'label' => 'Device Type', 'type' => 'select', 'required' => true, 'options' => [['value' => 'desktop', 'label' => 'Desktop Workstation'], ['value' => 'laptop', 'label' => 'Laptop'], ['value' => 'tablet', 'label' => 'Clinical Tablet'], ['value' => 'monitor_bundle', 'label' => 'Monitor Bundle']]],
                    ['key' => 'primary_user', 'label' => 'Primary User or Station', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Nurse Station B or Dr. Chen'],
                    ['key' => 'performance_profile', 'label' => 'Performance Profile', 'type' => 'select', 'required' => true, 'options' => [['value' => 'standard', 'label' => 'Standard clinical'], ['value' => 'performance', 'label' => 'Performance imaging/admin'], ['value' => 'mobile_rounding', 'label' => 'Mobile rounding']]],
                    ['key' => 'delivery_location', 'label' => 'Delivery Location', 'type' => 'text', 'required' => true, 'placeholder' => 'Building, floor, room'],
                    ['key' => 'needed_by', 'label' => 'Needed By', 'type' => 'date', 'required' => false],
                ],
            ],
            [
                'type' => 'software_install',
                'name' => 'Software Installation',
                'short_description' => 'Install licensed clinical, admin, or utility software on an approved device.',
                'description' => 'Request software installation with licensing and target asset details.',
                'icon' => 'bi-cloud-download',
                'color' => '#059669',
                'category' => 'Software',
                'default_priority' => 'medium',
                'approval_mode' => 'department_head',
                'fulfillment_category_id' => 2,
                'sla_hours' => 48,
                'sort_order' => 20,
                'schema' => [
                    ['key' => 'software_name', 'label' => 'Software Name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. PACS client, Adobe Acrobat Pro'],
                    ['key' => 'license_source', 'label' => 'License Source', 'type' => 'select', 'required' => true, 'options' => [['value' => 'existing_license', 'label' => 'Existing hospital license'], ['value' => 'new_purchase', 'label' => 'New purchase required'], ['value' => 'freeware', 'label' => 'Approved freeware']]],
                    ['key' => 'target_asset_tag', 'label' => 'Target Asset Tag', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. IT-LAP-0421'],
                    ['key' => 'operating_system', 'label' => 'Operating System', 'type' => 'select', 'required' => false, 'options' => [['value' => 'windows', 'label' => 'Windows'], ['value' => 'macos', 'label' => 'macOS'], ['value' => 'ios', 'label' => 'iOS'], ['value' => 'android', 'label' => 'Android']]],
                ],
            ],
            [
                'type' => 'email_creation',
                'name' => 'Email Creation',
                'short_description' => 'Create a mailbox, shared mailbox, alias, or distribution group.',
                'description' => 'Request email account creation with naming, manager, and onboarding details.',
                'icon' => 'bi-envelope-at',
                'color' => '#d97706',
                'category' => 'Identity',
                'default_priority' => 'medium',
                'approval_mode' => 'department_head',
                'fulfillment_category_id' => 6,
                'sla_hours' => 24,
                'sort_order' => 30,
                'schema' => [
                    ['key' => 'employee_first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
                    ['key' => 'employee_last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => true],
                    ['key' => 'employee_id', 'label' => 'Employee ID', 'type' => 'text', 'required' => false],
                    ['key' => 'mailbox_type', 'label' => 'Mailbox Type', 'type' => 'select', 'required' => true, 'options' => [['value' => 'user_mailbox', 'label' => 'User mailbox'], ['value' => 'shared_mailbox', 'label' => 'Shared mailbox'], ['value' => 'distribution_list', 'label' => 'Distribution list']]],
                    ['key' => 'desired_address', 'label' => 'Desired Address', 'type' => 'email', 'required' => false, 'placeholder' => 'name@healthcentral.org'],
                    ['key' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => false],
                ],
            ],
            [
                'type' => 'access_request',
                'name' => 'Access Request',
                'short_description' => 'Request application permissions, badge access, VPN, or privileged roles.',
                'description' => 'Request access with a system, level, expiration, and business justification.',
                'icon' => 'bi-shield-lock',
                'color' => '#7c3aed',
                'category' => 'Security',
                'default_priority' => 'medium',
                'approval_mode' => 'administrator',
                'fulfillment_category_id' => 6,
                'sla_hours' => 24,
                'sort_order' => 40,
                'schema' => [
                    ['key' => 'target_system', 'label' => 'Target System', 'type' => 'select', 'required' => true, 'options' => [['value' => 'emr', 'label' => 'Electronic Medical Records'], ['value' => 'pacs', 'label' => 'PACS Imaging'], ['value' => 'pharmacy', 'label' => 'Pharmacy System'], ['value' => 'billing', 'label' => 'Billing and Finance'], ['value' => 'badge_access', 'label' => 'Badge Access']]],
                    ['key' => 'access_level', 'label' => 'Access Level', 'type' => 'select', 'required' => true, 'options' => [['value' => 'read_only', 'label' => 'Read only'], ['value' => 'standard', 'label' => 'Standard user'], ['value' => 'supervisor', 'label' => 'Supervisor'], ['value' => 'admin', 'label' => 'Administrator']]],
                    ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date', 'required' => false],
                    ['key' => 'justification', 'label' => 'Access Justification', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Explain the clinical or operational need.'],
                ],
            ],
            [
                'type' => 'network_access',
                'name' => 'Network Access',
                'short_description' => 'Request network ports, Wi-Fi, VPN, firewall, or device onboarding.',
                'description' => 'Request connectivity changes with device identity and access scope.',
                'icon' => 'bi-wifi',
                'color' => '#0891b2',
                'category' => 'Network',
                'default_priority' => 'medium',
                'approval_mode' => 'administrator',
                'fulfillment_category_id' => 3,
                'sla_hours' => 48,
                'sort_order' => 50,
                'schema' => [
                    ['key' => 'access_type', 'label' => 'Access Type', 'type' => 'select', 'required' => true, 'options' => [['value' => 'wired_port', 'label' => 'Wired port activation'], ['value' => 'wifi', 'label' => 'Wi-Fi access'], ['value' => 'vpn', 'label' => 'VPN access'], ['value' => 'firewall_rule', 'label' => 'Firewall rule']]],
                    ['key' => 'device_name', 'label' => 'Device Name', 'type' => 'text', 'required' => true],
                    ['key' => 'mac_address', 'label' => 'MAC Address', 'type' => 'text', 'required' => false, 'placeholder' => 'AA:BB:CC:DD:EE:FF'],
                    ['key' => 'network_location', 'label' => 'Location or VLAN', 'type' => 'text', 'required' => true],
                    ['key' => 'business_owner', 'label' => 'Business Owner', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'type' => 'equipment_request',
                'name' => 'Equipment Request',
                'short_description' => 'Request clinical equipment, loaner devices, accessories, or spare hardware.',
                'description' => 'Request hospital equipment with quantity, urgency, and delivery details.',
                'icon' => 'bi-hdd-stack',
                'color' => '#dc2626',
                'category' => 'Equipment',
                'default_priority' => 'medium',
                'approval_mode' => 'department_head',
                'fulfillment_category_id' => 4,
                'sla_hours' => 72,
                'sort_order' => 60,
                'schema' => [
                    ['key' => 'equipment_type', 'label' => 'Equipment Type', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. infusion pump, monitor, barcode scanner'],
                    ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => true],
                    ['key' => 'request_reason', 'label' => 'Reason', 'type' => 'select', 'required' => true, 'options' => [['value' => 'new_service', 'label' => 'New service or station'], ['value' => 'replacement', 'label' => 'Replacement'], ['value' => 'temporary_loan', 'label' => 'Temporary loan'], ['value' => 'surge_capacity', 'label' => 'Surge capacity']]],
                    ['key' => 'delivery_location', 'label' => 'Delivery Location', 'type' => 'text', 'required' => true],
                    ['key' => 'needed_by', 'label' => 'Needed By', 'type' => 'date', 'required' => false],
                ],
            ],
        ];
    }
}
