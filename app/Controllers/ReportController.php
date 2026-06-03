<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Database;
use App\Helpers\Session;

class ReportController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // General Reports dashboard landing
        // Fetch some high level stats to show on reports dashboard
        $stats = [
            'tickets_count' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL"),
            'assets_count' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL"),
            'maintenance_cost' => (float)$this->db->fetchColumn("SELECT SUM(cost) FROM maintenance_tasks WHERE status='completed' AND deleted_at IS NULL"),
            'inventory_value' => (float)$this->db->fetchColumn("SELECT SUM(quantity * unit_cost) FROM inventory_items WHERE deleted_at IS NULL"),
        ];

        $this->view('reports/index', [
            'pageTitle' => 'System Reports & Analytics',
            'stats' => $stats
        ]);
    }

    public function tickets(): void
    {
        $statusCounts = $this->db->fetchAll("SELECT status, COUNT(*) as cnt FROM tickets WHERE deleted_at IS NULL GROUP BY status");
        $priorityCounts = $this->db->fetchAll("SELECT priority, COUNT(*) as cnt FROM tickets WHERE deleted_at IS NULL GROUP BY priority");
        $deptCounts = $this->db->fetchAll(
            "SELECT d.name, COUNT(t.id) as cnt 
             FROM tickets t 
             JOIN departments d ON t.department_id = d.id 
             WHERE t.deleted_at IS NULL GROUP BY d.name"
        );

        $this->view('reports/tickets', [
            'pageTitle' => 'Incidents Report Summary',
            'statusCounts' => $statusCounts,
            'priorityCounts' => $priorityCounts,
            'deptCounts' => $deptCounts
        ]);
    }

    public function assets(): void
    {
        $statusCounts = $this->db->fetchAll("SELECT status, COUNT(*) as cnt FROM assets WHERE deleted_at IS NULL GROUP BY status");
        $categoryCounts = $this->db->fetchAll(
            "SELECT ac.name, COUNT(a.id) as cnt 
             FROM assets a 
             JOIN asset_categories ac ON a.category_id = ac.id 
             WHERE a.deleted_at IS NULL GROUP BY ac.name"
        );
        $expiringWarranty = $this->db->fetchAll(
            "SELECT id, asset_tag, name, warranty_expiry 
             FROM assets 
             WHERE warranty_expiry IS NOT NULL AND warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) 
             AND deleted_at IS NULL ORDER BY warranty_expiry ASC"
        );

        $this->view('reports/assets', [
            'pageTitle' => 'Assets Health & Lifecycle Report',
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'expiringWarranty' => $expiringWarranty
        ]);
    }

    public function maintenance(): void
    {
        $statusCounts = $this->db->fetchAll("SELECT status, COUNT(*) as cnt FROM maintenance_tasks WHERE deleted_at IS NULL GROUP BY status");
        $typeCost = $this->db->fetchAll(
            "SELECT type, COUNT(*) as cnt, SUM(cost) as total_cost 
             FROM maintenance_tasks 
             WHERE deleted_at IS NULL GROUP BY type"
        );

        $this->view('reports/maintenance', [
            'pageTitle' => 'Maintenance Operations & Costs',
            'statusCounts' => $statusCounts,
            'typeCost' => $typeCost
        ]);
    }

    public function inventory(): void
    {
        $lowStock = $this->db->fetchAll(
            "SELECT i.*, ic.name as category_name 
             FROM inventory_items i
             LEFT JOIN inventory_categories ic ON i.category_id = ic.id
             WHERE i.quantity <= i.reorder_level AND i.deleted_at IS NULL"
        );
        $topValuation = $this->db->fetchAll(
            "SELECT name, sku, (quantity * unit_cost) as total_val 
             FROM inventory_items 
             WHERE deleted_at IS NULL ORDER BY total_val DESC LIMIT 10"
        );

        $this->view('reports/inventory', [
            'pageTitle' => 'Stock Levels & Reorder Report',
            'lowStock' => $lowStock,
            'topValuation' => $topValuation
        ]);
    }

    public function sla(): void
    {
        $slaStatus = $this->db->fetchAll("SELECT sla_status, COUNT(*) as cnt FROM tickets WHERE deleted_at IS NULL GROUP BY sla_status");
        
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL");
        $met = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE sla_status = 'on_track' AND deleted_at IS NULL");
        $compliancePct = $total > 0 ? round(($met / $total) * 100, 1) : 100;

        $avgResolution = $this->db->fetchColumn(
            "SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)), 0) 
             FROM tickets WHERE resolved_at IS NOT NULL AND deleted_at IS NULL"
        );

        $this->view('reports/sla', [
            'pageTitle' => 'SLA Performance Tracker',
            'slaStatus' => $slaStatus,
            'compliance' => $compliancePct,
            'avg_resolution' => round($avgResolution / 60, 1) // In hours
        ]);
    }

    public function export(string $type): void
    {
        $format = $_GET['format'] ?? 'csv';

        $data = [];
        $filename = 'export_' . $type . '_' . date('YmdHis');
        $headers = [];

        switch ($type) {
            case 'tickets':
                $headers = ['Ticket No', 'Title', 'Priority', 'Status', 'Requester', 'Assignee', 'Created At'];
                $rows = $this->db->fetchAll(
                    "SELECT t.ticket_number, t.title, t.priority, t.status, 
                            CONCAT(req.first_name, ' ', req.last_name) as req_name,
                            CONCAT(asg.first_name, ' ', asg.last_name) as asg_name,
                            t.created_at
                     FROM tickets t
                     LEFT JOIN users req ON t.requester_id = req.id
                     LEFT JOIN users asg ON t.assigned_to = asg.id
                     WHERE t.deleted_at IS NULL"
                );
                foreach ($rows as $r) {
                    $data[] = [$r->ticket_number, $r->title, $r->priority, $r->status, $r->req_name, $r->asg_name ?? 'Unassigned', $r->created_at];
                }
                break;

            case 'assets':
                $headers = ['Asset Tag', 'Name', 'Serial', 'Manufacturer', 'Model', 'Status', 'Warranty Expiry'];
                $rows = $this->db->fetchAll("SELECT asset_tag, name, serial_number, manufacturer, model, status, warranty_expiry FROM assets WHERE deleted_at IS NULL");
                foreach ($rows as $r) {
                    $data[] = [$r->asset_tag, $r->name, $r->serial_number, $r->manufacturer, $r->model, $r->status, $r->warranty_expiry];
                }
                break;

            case 'inventory':
                $headers = ['SKU', 'Name', 'Quantity', 'Reorder Level', 'Unit Cost', 'Total Value', 'Location'];
                $rows = $this->db->fetchAll("SELECT sku, name, quantity, reorder_level, unit_cost, location FROM inventory_items WHERE deleted_at IS NULL");
                foreach ($rows as $r) {
                    $val = $r->quantity * ($r->unit_cost ?? 0);
                    $data[] = [$r->sku, $r->name, $r->quantity, $r->reorder_level, $r->unit_cost, $val, $r->location];
                }
                break;

            case 'maintenance':
                $headers = ['WO ID', 'Title', 'Asset Tag', 'Type', 'Priority', 'Status', 'Scheduled Date', 'Cost'];
                $rows = $this->db->fetchAll(
                    "SELECT m.id, m.title, a.asset_tag, m.type, m.priority, m.status, m.scheduled_date, m.cost
                     FROM maintenance_tasks m
                     LEFT JOIN assets a ON m.asset_id = a.id
                     WHERE m.deleted_at IS NULL"
                );
                foreach ($rows as $r) {
                    $data[] = ['WO-' . $r->id, $r->title, $r->asset_tag ?? 'None', $r->type, $r->priority, $r->status, $r->scheduled_date, $r->cost];
                }
                break;

            default:
                $this->abort(404);
                return;
        }

        if ($format === 'excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            // Output as clean HTML table for Microsoft Excel
            echo "<table border='1'><thead><tr>";
            foreach ($headers as $h) {
                echo "<th>" . htmlspecialchars($h) . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
            exit;
        } else {
            // Default CSV stream
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    }
}
