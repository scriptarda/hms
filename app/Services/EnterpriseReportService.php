<?php
namespace App\Services;

use App\Helpers\SimplePdf;
use App\Repositories\EnterpriseReportRepository;

class EnterpriseReportService
{
    private EnterpriseReportRepository $repo;

    public function __construct()
    {
        $this->repo = new EnterpriseReportRepository();
    }

    public function reportTypes(): array
    {
        return [
            'tickets' => ['label' => 'Ticket Reports', 'icon' => 'bi-exclamation-triangle', 'color' => 'primary'],
            'assets' => ['label' => 'Asset Reports', 'icon' => 'bi-hdd-stack', 'color' => 'success'],
            'sla' => ['label' => 'SLA Reports', 'icon' => 'bi-hourglass-split', 'color' => 'danger'],
            'maintenance' => ['label' => 'Maintenance Reports', 'icon' => 'bi-wrench-adjustable', 'color' => 'warning'],
            'user_activity' => ['label' => 'User Activity Reports', 'icon' => 'bi-person-lines-fill', 'color' => 'secondary'],
            'inventory' => ['label' => 'Inventory Reports', 'icon' => 'bi-box-seam', 'color' => 'info'],
        ];
    }

    public function overview(int $userId): array
    {
        $db = $this->repo->db();
        return [
            'reportTypes' => $this->reportTypes(),
            'stats' => [
                'tickets_count' => (int)$db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL"),
                'assets_count' => (int)$db->fetchColumn("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL"),
                'maintenance_cost' => (float)$db->fetchColumn("SELECT COALESCE(SUM(cost),0) FROM maintenance_tasks WHERE status='completed' AND deleted_at IS NULL"),
                'user_activity' => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
                'scheduled_reports' => count($this->repo->schedules($userId)),
            ],
            'schedules' => $this->repo->schedules($userId),
            'exports' => $this->repo->recentExports($userId),
        ];
    }

    public function filters(array $input): array
    {
        $keys = ['date_from', 'date_to', 'status', 'priority', 'department_id', 'assigned_to', 'category_id', 'user_id', 'action', 'type', 'severity', 'search'];
        $filters = [];
        foreach ($keys as $key) {
            $filters[$key] = trim((string)($input[$key] ?? ''));
        }
        return $filters;
    }

    public function report(string $type, array $filters): array
    {
        return match ($type) {
            'tickets' => $this->ticketReport($filters),
            'assets' => $this->assetReport($filters),
            'sla' => $this->slaReport($filters),
            'maintenance' => $this->maintenanceReport($filters),
            'user_activity' => $this->userActivityReport($filters),
            'inventory' => $this->inventoryReport($filters),
            default => throw new \InvalidArgumentException('Unsupported report type.'),
        };
    }

    public function filterOptions(): array
    {
        return [
            'departments' => $this->repo->departments(),
            'users' => $this->repo->users(),
            'assetCategories' => $this->repo->assetCategories(),
            'ticketCategories' => $this->repo->ticketCategories(),
            'inventoryCategories' => $this->repo->inventoryCategories(),
        ];
    }

    public function export(string $type, string $format, array $filters): array
    {
        $format = in_array($format, ['pdf', 'excel', 'csv'], true) ? $format : 'csv';
        $report = $this->report($type, $filters);
        $filename = $type . '_report_' . date('Ymd_His') . '.' . ($format === 'excel' ? 'xls' : $format);

        if ($format === 'pdf') {
            return [
                'filename' => $filename,
                'content_type' => 'application/pdf',
                'content' => SimplePdf::table($report['title'], $report['headers'], $report['rows'], $report['kpis']),
            ];
        }

        if ($format === 'excel') {
            return [
                'filename' => $filename,
                'content_type' => 'application/vnd.ms-excel',
                'content' => $this->excelContent($report),
            ];
        }

        return [
            'filename' => $filename,
            'content_type' => 'text/csv; charset=utf-8',
            'content' => $this->csvContent($report['headers'], $report['rows']),
        ];
    }

    public function createSchedule(int $userId, array $input): array
    {
        $reportType = $input['report_type'] ?? 'tickets';
        if (!isset($this->reportTypes()[$reportType])) {
            return ['success' => false, 'message' => 'Report type is not valid.'];
        }

        $frequency = in_array($input['frequency'] ?? 'weekly', ['daily', 'weekly', 'monthly'], true) ? $input['frequency'] : 'weekly';
        $format = in_array($input['format'] ?? 'pdf', ['pdf', 'excel', 'csv'], true) ? $input['format'] : 'pdf';
        $filters = json_decode($input['filters_json'] ?? '[]', true) ?: [];

        $id = $this->repo->createSchedule([
            'user_id' => $userId,
            'name' => trim($input['name'] ?? '') ?: $this->reportTypes()[$reportType]['label'],
            'report_type' => $reportType,
            'format' => $format,
            'frequency' => $frequency,
            'filters_json' => json_encode($filters),
            'recipients' => trim($input['recipients'] ?? ''),
            'channels_json' => json_encode($input['channels'] ?? ['in_app']),
            'next_run_at' => $this->nextRunAt($frequency),
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Scheduled report created.'];
    }

    public function toggleSchedule(int $id, int $userId): void
    {
        $this->repo->toggleSchedule($id, $userId);
    }

    public function runDueSchedules(int $limit = 25): array
    {
        $processed = 0;
        $failed = 0;

        foreach ($this->repo->dueSchedules($limit) as $schedule) {
            $processed++;
            try {
                $filters = json_decode($schedule->filters_json ?? '[]', true) ?: [];
                $export = $this->export($schedule->report_type, $schedule->format, $filters);
                $dir = STORAGE_PATH . '/reports';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $filePath = $dir . '/' . $export['filename'];
                file_put_contents($filePath, $export['content']);
                $report = $this->report($schedule->report_type, $filters);

                $this->repo->createExport([
                    'schedule_id' => (int)$schedule->id,
                    'user_id' => (int)$schedule->user_id,
                    'report_type' => $schedule->report_type,
                    'format' => $schedule->format,
                    'file_path' => $filePath,
                    'row_count' => count($report['rows']),
                    'status' => 'generated',
                ]);

                (new NotificationService())->send(
                    (int)$schedule->user_id,
                    NOTIFY_REPORT_READY,
                    'Scheduled Report Ready',
                    $schedule->name . ' generated ' . count($report['rows']) . ' rows.',
                    '/reports',
                    ['data' => ['file_path' => $filePath, 'report_type' => $schedule->report_type]]
                );
                $this->repo->updateScheduleRun((int)$schedule->id, $this->nextRunAt($schedule->frequency));
            } catch (\Exception $e) {
                $failed++;
                $this->repo->createExport([
                    'schedule_id' => (int)$schedule->id,
                    'user_id' => (int)$schedule->user_id,
                    'report_type' => $schedule->report_type,
                    'format' => $schedule->format,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                (new NotificationService())->send(
                    (int)$schedule->user_id,
                    NOTIFY_REPORT_FAILED,
                    'Scheduled Report Failed',
                    $schedule->name . ' failed: ' . $e->getMessage(),
                    '/reports'
                );
                $this->repo->updateScheduleRun((int)$schedule->id, $this->nextRunAt($schedule->frequency));
            }
        }

        return ['success' => true, 'processed' => $processed, 'failed' => $failed, 'ran_at' => date('Y-m-d H:i:s')];
    }

    private function ticketReport(array $filters): array
    {
        [$where, $params] = $this->ticketWhere($filters);
        $db = $this->repo->db();

        $rows = $db->fetchAll(
            "SELECT t.ticket_number, t.title, t.priority, t.status, t.sla_status,
                    COALESCE(d.name, '-') as department_name,
                    COALESCE(CONCAT(req.first_name, ' ', req.last_name), '-') as requester_name,
                    COALESCE(CONCAT(asgn.first_name, ' ', asgn.last_name), 'Unassigned') as assignee_name,
                    t.created_at, t.resolved_at
             FROM tickets t
             LEFT JOIN departments d ON t.department_id = d.id
             LEFT JOIN users req ON t.requester_id = req.id
             LEFT JOIN users asgn ON t.assigned_to = asgn.id
             WHERE {$where}
             ORDER BY t.created_at DESC
             LIMIT 1000",
            $params
        );

        return [
            'type' => 'tickets',
            'title' => 'Ticket Reports',
            'description' => 'Ticket volume, SLA exposure, priorities, queues, and ownership.',
            'filters' => $filters,
            'headers' => [
                'ticket_number' => 'Ticket #',
                'title' => 'Title',
                'priority' => 'Priority',
                'status' => 'Status',
                'sla_status' => 'SLA',
                'department_name' => 'Department',
                'requester_name' => 'Requester',
                'assignee_name' => 'Assignee',
                'created_at' => 'Created',
                'resolved_at' => 'Resolved',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'Tickets', 'value' => count($rows), 'icon' => 'bi-ticket-detailed', 'color' => 'blue'],
                ['label' => 'Open', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM tickets t WHERE {$where} AND t.status NOT IN ('resolved','closed')", $params), 'icon' => 'bi-folder2-open', 'color' => 'yellow'],
                ['label' => 'SLA Breached', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM tickets t WHERE {$where} AND t.sla_status='breached'", $params), 'icon' => 'bi-alarm', 'color' => 'red'],
                ['label' => 'Avg Resolution', 'value' => round(((float)$db->fetchColumn("SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at)),0) FROM tickets t WHERE {$where} AND t.resolved_at IS NOT NULL", $params)) / 60, 1) . 'h', 'icon' => 'bi-stopwatch', 'color' => 'green'],
            ],
            'charts' => [
                $this->groupChart('Status', 'bar', $db->fetchAll("SELECT t.status as label, COUNT(*) as value FROM tickets t WHERE {$where} GROUP BY t.status", $params)),
                $this->groupChart('Priority', 'doughnut', $db->fetchAll("SELECT t.priority as label, COUNT(*) as value FROM tickets t WHERE {$where} GROUP BY t.priority", $params)),
            ],
        ];
    }

    private function assetReport(array $filters): array
    {
        [$where, $params] = $this->assetWhere($filters);
        $db = $this->repo->db();
        $rows = $db->fetchAll(
            "SELECT a.asset_tag, a.name, a.serial_number, a.manufacturer, a.model, a.status,
                    COALESCE(ac.name, '-') as category_name, COALESCE(d.name, '-') as department_name,
                    a.purchase_date, a.warranty_expiry
             FROM assets a
             LEFT JOIN asset_categories ac ON a.category_id = ac.id
             LEFT JOIN departments d ON a.department_id = d.id
             WHERE {$where}
             ORDER BY a.created_at DESC
             LIMIT 1000",
            $params
        );

        return [
            'type' => 'assets',
            'title' => 'Asset Reports',
            'description' => 'Registry health, warranty exposure, ownership, and asset lifecycle state.',
            'filters' => $filters,
            'headers' => [
                'asset_tag' => 'Asset Tag',
                'name' => 'Name',
                'serial_number' => 'Serial',
                'manufacturer' => 'Manufacturer',
                'model' => 'Model',
                'status' => 'Status',
                'category_name' => 'Category',
                'department_name' => 'Department',
                'purchase_date' => 'Purchase Date',
                'warranty_expiry' => 'Warranty Expiry',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'Assets', 'value' => count($rows), 'icon' => 'bi-hdd-stack', 'color' => 'blue'],
                ['label' => 'Active', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM assets a WHERE {$where} AND a.status='active'", $params), 'icon' => 'bi-check-circle', 'color' => 'green'],
                ['label' => 'Maintenance', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM assets a WHERE {$where} AND a.status='maintenance'", $params), 'icon' => 'bi-wrench', 'color' => 'yellow'],
                ['label' => 'Warranty 90d', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM assets a WHERE {$where} AND a.warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)", $params), 'icon' => 'bi-shield-exclamation', 'color' => 'red'],
            ],
            'charts' => [
                $this->groupChart('Status', 'pie', $db->fetchAll("SELECT a.status as label, COUNT(*) as value FROM assets a WHERE {$where} GROUP BY a.status", $params)),
                $this->groupChart('Category', 'doughnut', $db->fetchAll("SELECT COALESCE(ac.name, 'Uncategorized') as label, COUNT(*) as value FROM assets a LEFT JOIN asset_categories ac ON a.category_id = ac.id WHERE {$where} GROUP BY label", $params)),
            ],
        ];
    }

    private function slaReport(array $filters): array
    {
        [$where, $params] = $this->ticketWhere($filters);
        $db = $this->repo->db();
        $rows = $db->fetchAll(
            "SELECT t.ticket_number, t.title, t.priority, t.status,
                    t.response_sla_status, t.resolution_sla_status, t.sla_status,
                    t.response_due_at, t.responded_at, COALESCE(t.resolution_due_at, t.sla_due_at) as resolution_due_at,
                    t.resolved_at, t.escalation_level
             FROM tickets t
             WHERE {$where}
             ORDER BY FIELD(t.sla_status,'breached','warning','on_track'), COALESCE(t.resolution_due_at, t.sla_due_at, t.created_at) ASC
             LIMIT 1000",
            $params
        );

        $metrics = (new SlaMonitorService())->dashboard()['metrics'];
        return [
            'type' => 'sla',
            'title' => 'SLA Reports',
            'description' => 'Response targets, resolution targets, breach risk, and escalation trends.',
            'filters' => $filters,
            'headers' => [
                'ticket_number' => 'Ticket #',
                'title' => 'Title',
                'priority' => 'Priority',
                'status' => 'Ticket Status',
                'response_sla_status' => 'Response SLA',
                'resolution_sla_status' => 'Resolution SLA',
                'sla_status' => 'Overall SLA',
                'response_due_at' => 'Response Due',
                'responded_at' => 'Responded',
                'resolution_due_at' => 'Resolution Due',
                'resolved_at' => 'Resolved',
                'escalation_level' => 'Escalation Level',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'Open Tickets', 'value' => $metrics['open_tickets'], 'icon' => 'bi-folder2-open', 'color' => 'blue'],
                ['label' => 'Response Breach', 'value' => $metrics['response_breached'], 'icon' => 'bi-hourglass-bottom', 'color' => 'red'],
                ['label' => 'Resolution Breach', 'value' => $metrics['resolution_breached'], 'icon' => 'bi-alarm', 'color' => 'red'],
                ['label' => 'Compliance', 'value' => number_format((float)$metrics['resolution_compliance'], 1) . '%', 'icon' => 'bi-shield-check', 'color' => 'green'],
            ],
            'charts' => [
                $this->groupChart('Overall SLA', 'bar', $db->fetchAll("SELECT t.sla_status as label, COUNT(*) as value FROM tickets t WHERE {$where} GROUP BY t.sla_status", $params)),
                $this->groupChart('Priority', 'doughnut', $db->fetchAll("SELECT t.priority as label, COUNT(*) as value FROM tickets t WHERE {$where} GROUP BY t.priority", $params)),
            ],
        ];
    }

    private function maintenanceReport(array $filters): array
    {
        [$where, $params] = $this->maintenanceWhere($filters);
        $db = $this->repo->db();
        $rows = $db->fetchAll(
            "SELECT COALESCE(m.work_order_number, CONCAT('WO-', m.id)) as work_order_number,
                    m.title, m.type, m.priority, m.status,
                    COALESCE(a.asset_tag, '-') as asset_tag,
                    COALESCE(d.name, '-') as department_name,
                    COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unassigned') as assignee_name,
                    m.scheduled_date, m.due_date, m.completed_date, m.actual_hours, m.cost
             FROM maintenance_tasks m
             LEFT JOIN assets a ON m.asset_id = a.id
             LEFT JOIN departments d ON m.department_id = d.id
             LEFT JOIN users u ON m.assigned_to = u.id
             WHERE {$where}
             ORDER BY COALESCE(m.due_date, m.scheduled_date) DESC
             LIMIT 1000",
            $params
        );

        return [
            'type' => 'maintenance',
            'title' => 'Maintenance Reports',
            'description' => 'Preventive and corrective work orders, schedule adherence, labor, and cost.',
            'filters' => $filters,
            'headers' => [
                'work_order_number' => 'Work Order',
                'title' => 'Title',
                'type' => 'Type',
                'priority' => 'Priority',
                'status' => 'Status',
                'asset_tag' => 'Asset',
                'department_name' => 'Department',
                'assignee_name' => 'Technician',
                'scheduled_date' => 'Scheduled',
                'due_date' => 'Due',
                'completed_date' => 'Completed',
                'actual_hours' => 'Hours',
                'cost' => 'Cost',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'Work Orders', 'value' => count($rows), 'icon' => 'bi-wrench-adjustable', 'color' => 'blue'],
                ['label' => 'Open', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks m WHERE {$where} AND m.status IN ('scheduled','in_progress','overdue')", $params), 'icon' => 'bi-list-check', 'color' => 'yellow'],
                ['label' => 'Overdue', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks m WHERE {$where} AND m.status='overdue'", $params), 'icon' => 'bi-exclamation-triangle', 'color' => 'red'],
                ['label' => 'Cost', 'value' => '$' . number_format((float)$db->fetchColumn("SELECT COALESCE(SUM(m.cost),0) FROM maintenance_tasks m WHERE {$where}", $params), 0), 'icon' => 'bi-cash-stack', 'color' => 'green'],
            ],
            'charts' => [
                $this->groupChart('Status', 'bar', $db->fetchAll("SELECT m.status as label, COUNT(*) as value FROM maintenance_tasks m WHERE {$where} GROUP BY m.status", $params)),
                $this->groupChart('Type', 'doughnut', $db->fetchAll("SELECT m.type as label, COUNT(*) as value FROM maintenance_tasks m WHERE {$where} GROUP BY m.type", $params)),
            ],
        ];
    }

    private function inventoryReport(array $filters): array
    {
        $where = "i.deleted_at IS NULL";
        $params = [];
        if ($filters['search'] !== '') {
            $where .= " AND (i.name LIKE ? OR i.sku LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if ($filters['category_id'] !== '') {
            $where .= " AND i.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $db = $this->repo->db();
        $rows = $db->fetchAll(
            "SELECT i.sku, i.name, COALESCE(ic.name, '-') as category_name, i.quantity, i.reorder_level,
                    i.unit, i.unit_cost, (i.quantity * COALESCE(i.unit_cost,0)) as total_value,
                    COALESCE(s.name, i.supplier, '-') as supplier_name, i.location
             FROM inventory_items i
             LEFT JOIN inventory_categories ic ON i.category_id = ic.id
             LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
             WHERE {$where}
             ORDER BY total_value DESC
             LIMIT 1000",
            $params
        );

        return [
            'type' => 'inventory',
            'title' => 'Inventory Reports',
            'description' => 'Spare parts valuation, reorder exposure, stock balances, and supplier coverage.',
            'filters' => $filters,
            'headers' => [
                'sku' => 'SKU',
                'name' => 'Item',
                'category_name' => 'Category',
                'quantity' => 'Quantity',
                'reorder_level' => 'Reorder Level',
                'unit' => 'Unit',
                'unit_cost' => 'Unit Cost',
                'total_value' => 'Total Value',
                'supplier_name' => 'Supplier',
                'location' => 'Location',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'SKUs', 'value' => count($rows), 'icon' => 'bi-box', 'color' => 'blue'],
                ['label' => 'Low Stock', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM inventory_items i WHERE {$where} AND i.quantity <= i.reorder_level", $params), 'icon' => 'bi-exclamation-triangle', 'color' => 'yellow'],
                ['label' => 'Out of Stock', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM inventory_items i WHERE {$where} AND i.quantity <= 0", $params), 'icon' => 'bi-x-octagon', 'color' => 'red'],
                ['label' => 'Value', 'value' => '$' . number_format((float)$db->fetchColumn("SELECT COALESCE(SUM(i.quantity * COALESCE(i.unit_cost,0)),0) FROM inventory_items i WHERE {$where}", $params), 0), 'icon' => 'bi-cash-stack', 'color' => 'green'],
            ],
            'charts' => [
                $this->groupChart('Category', 'doughnut', $db->fetchAll("SELECT COALESCE(ic.name, 'Uncategorized') as label, COUNT(*) as value FROM inventory_items i LEFT JOIN inventory_categories ic ON i.category_id = ic.id WHERE {$where} GROUP BY label", $params)),
                $this->groupChart('Stock Risk', 'bar', [
                    (object)['label' => 'Low', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM inventory_items i WHERE {$where} AND i.quantity <= i.reorder_level AND i.quantity > 0", $params)],
                    (object)['label' => 'Out', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM inventory_items i WHERE {$where} AND i.quantity <= 0", $params)],
                ]),
            ],
        ];
    }

    private function userActivityReport(array $filters): array
    {
        [$where, $params] = $this->auditWhere($filters);
        $db = $this->repo->db();
        $rows = $db->fetchAll(
            "SELECT COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') as user_name,
                    COALESCE(u.email, '-') as email,
                    al.action, COALESCE(al.entity_type, '-') as entity_type,
                    COALESCE(al.entity_id, '-') as entity_id,
                    al.ip_address, al.created_at
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE {$where}
             ORDER BY al.created_at DESC
             LIMIT 1000",
            $params
        );

        return [
            'type' => 'user_activity',
            'title' => 'User Activity Reports',
            'description' => 'Audit trail, login activity, user actions, and operational accountability.',
            'filters' => $filters,
            'headers' => [
                'user_name' => 'User',
                'email' => 'Email',
                'action' => 'Action',
                'entity_type' => 'Entity',
                'entity_id' => 'Entity ID',
                'ip_address' => 'IP Address',
                'created_at' => 'When',
            ],
            'rows' => $this->normalizeRows($rows),
            'kpis' => [
                ['label' => 'Events', 'value' => count($rows), 'icon' => 'bi-activity', 'color' => 'blue'],
                ['label' => 'Users', 'value' => (int)$db->fetchColumn("SELECT COUNT(DISTINCT al.user_id) FROM audit_logs al WHERE {$where} AND al.user_id IS NOT NULL", $params), 'icon' => 'bi-people', 'color' => 'green'],
                ['label' => 'Failed Logins', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs al WHERE {$where} AND al.action='login_failed'", $params), 'icon' => 'bi-shield-x', 'color' => 'red'],
                ['label' => 'Today', 'value' => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs al WHERE {$where} AND DATE(al.created_at)=CURDATE()", $params), 'icon' => 'bi-calendar-event', 'color' => 'yellow'],
            ],
            'charts' => [
                $this->groupChart('Actions', 'bar', $db->fetchAll("SELECT al.action as label, COUNT(*) as value FROM audit_logs al WHERE {$where} GROUP BY al.action ORDER BY value DESC LIMIT 8", $params)),
                $this->groupChart('Entities', 'doughnut', $db->fetchAll("SELECT COALESCE(al.entity_type, 'system') as label, COUNT(*) as value FROM audit_logs al WHERE {$where} GROUP BY label ORDER BY value DESC LIMIT 8", $params)),
            ],
        ];
    }

    private function ticketWhere(array $filters): array
    {
        $where = ["t.deleted_at IS NULL"];
        $params = [];
        $this->dateFilter($where, $params, 't.created_at', $filters);
        foreach (['status', 'priority', 'department_id', 'assigned_to', 'category_id'] as $field) {
            if ($filters[$field] !== '') {
                $where[] = "t.{$field} = ?";
                $params[] = in_array($field, ['department_id', 'assigned_to', 'category_id'], true) ? (int)$filters[$field] : $filters[$field];
            }
        }
        if ($filters['search'] !== '') {
            $where[] = "(t.ticket_number LIKE ? OR t.title LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        return [implode(' AND ', $where), $params];
    }

    private function assetWhere(array $filters): array
    {
        $where = ["a.deleted_at IS NULL"];
        $params = [];
        $this->dateFilter($where, $params, 'a.created_at', $filters);
        foreach (['status', 'department_id', 'category_id'] as $field) {
            if ($filters[$field] !== '') {
                $where[] = "a.{$field} = ?";
                $params[] = in_array($field, ['department_id', 'category_id'], true) ? (int)$filters[$field] : $filters[$field];
            }
        }
        if ($filters['search'] !== '') {
            $where[] = "(a.asset_tag LIKE ? OR a.name LIKE ? OR a.serial_number LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        return [implode(' AND ', $where), $params];
    }

    private function maintenanceWhere(array $filters): array
    {
        $where = ["m.deleted_at IS NULL"];
        $params = [];
        $this->dateFilter($where, $params, 'COALESCE(m.scheduled_date, DATE(m.created_at))', $filters);
        foreach (['status', 'priority', 'type', 'department_id', 'assigned_to'] as $field) {
            if ($filters[$field] !== '') {
                $where[] = "m.{$field} = ?";
                $params[] = in_array($field, ['department_id', 'assigned_to'], true) ? (int)$filters[$field] : $filters[$field];
            }
        }
        if ($filters['search'] !== '') {
            $where[] = "(m.work_order_number LIKE ? OR m.title LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        return [implode(' AND ', $where), $params];
    }

    private function auditWhere(array $filters): array
    {
        $where = ["1=1"];
        $params = [];
        $this->dateFilter($where, $params, 'al.created_at', $filters);
        if ($filters['user_id'] !== '') {
            $where[] = "al.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if ($filters['action'] !== '') {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        if ($filters['search'] !== '') {
            $where[] = "(al.action LIKE ? OR al.entity_type LIKE ? OR al.ip_address LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        return [implode(' AND ', $where), $params];
    }

    private function dateFilter(array &$where, array &$params, string $column, array $filters): void
    {
        if ($filters['date_from'] !== '') {
            $where[] = "DATE({$column}) >= ?";
            $params[] = $filters['date_from'];
        }
        if ($filters['date_to'] !== '') {
            $where[] = "DATE({$column}) <= ?";
            $params[] = $filters['date_to'];
        }
    }

    private function groupChart(string $title, string $type, array $rows): array
    {
        return [
            'title' => $title,
            'type' => $type,
            'labels' => array_map(fn($row) => ucwords(str_replace('_', ' ', (string)$row->label)), $rows),
            'data' => array_map(fn($row) => (int)$row->value, $rows),
        ];
    }

    private function normalizeRows(array $rows): array
    {
        return array_map(function (object $row): array {
            $data = [];
            foreach ((array)$row as $key => $value) {
                if ($value === null || $value === '') {
                    $data[$key] = '-';
                } elseif (is_numeric($value) && (str_contains($key, 'cost') || str_contains($key, 'value'))) {
                    $data[$key] = is_numeric($value) ? number_format((float)$value, 2) : $value;
                } else {
                    $data[$key] = (string)$value;
                }
            }
            return $data;
        }, $rows);
    }

    private function csvContent(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_values($headers));
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn($key) => $row[$key] ?? '', array_keys($headers)));
        }
        rewind($stream);
        return stream_get_contents($stream);
    }

    private function excelContent(array $report): string
    {
        $html = '<table border="1"><thead><tr><th colspan="' . count($report['headers']) . '">' . htmlspecialchars($report['title']) . '</th></tr><tr>';
        foreach ($report['headers'] as $label) {
            $html .= '<th>' . htmlspecialchars($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($report['rows'] as $row) {
            $html .= '<tr>';
            foreach (array_keys($report['headers']) as $key) {
                $html .= '<td>' . htmlspecialchars($row[$key] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private function nextRunAt(string $frequency): string
    {
        return match ($frequency) {
            'daily' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'monthly' => date('Y-m-d H:i:s', strtotime('+1 month')),
            default => date('Y-m-d H:i:s', strtotime('+1 week')),
        };
    }
}
