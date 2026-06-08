<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\Database;
use App\Services\AssetService;
use App\Services\NotificationService;
use App\Services\SlaMonitorService;

class AssetController extends BaseController
{
    private Database $db;
    private AssetService $service;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->service = new AssetService();
    }

    public function index(): void
    {
        $filters = $this->assetFilters();
        $formData = $this->service->formData();

        $this->view('assets/index', [
            'pageTitle' => 'Asset Registry',
            'assets' => $this->service->registry($filters),
            'metrics' => $this->service->metrics(),
            'categories' => $formData['categories'],
            'departments' => $formData['departments'],
            'users' => $formData['users'],
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $data = $this->service->formData();
        $data['pageTitle'] = 'Register Asset';
        $data['floors'] = $this->floors();
        $data['rooms'] = $this->rooms();

        $this->view('assets/create', $data);
    }

    public function store(): void
    {
        $result = $this->service->create($_POST, Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/assets/create');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/assets/' . $result['id']);
    }

    public function show(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $bundle['pageTitle'] = 'Asset: ' . $bundle['asset']->asset_tag;
        $this->view('assets/show', $bundle);
    }

    public function edit(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $data = array_merge($this->service->formData(), [
            'pageTitle' => 'Edit Asset ' . $bundle['asset']->asset_tag,
            'asset' => $bundle['asset'],
            'activeAssignment' => $bundle['activeAssignment'],
            'floors' => $this->floors(),
            'rooms' => $this->rooms(),
        ]);

        $this->view('assets/edit', $data);
    }

    public function update(string $id): void
    {
        $result = $this->service->update((int)$id, $_POST, Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/assets/' . $id . '/edit');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/assets/' . $id);
    }

    public function delete(string $id): void
    {
        if (!$this->canManage()) {
            $this->abort(403);
        }

        $result = $this->service->delete((int)$id, Session::userId());
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/assets');
    }

    public function assignAsset(string $id): void
    {
        if (!$this->canManage()) {
            $this->abort(403);
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            Session::flash('error', 'Please select a user to assign.');
            $this->redirect('/assets/' . $id);
        }

        $result = $this->service->assign((int)$id, $userId, Session::userId(), trim($_POST['notes'] ?? ''));
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/assets/' . $id);
    }

    public function returnAsset(string $id): void
    {
        if (!$this->canManage()) {
            $this->abort(403);
        }

        $result = $this->service->returnAssignment((int)$id, Session::userId(), trim($_POST['notes'] ?? ''));
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/assets/' . $id);
    }

    public function generateQR(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('assets/qr', [
            'pageTitle' => 'Print Asset Label: ' . $bundle['asset']->asset_tag,
            'asset' => $bundle['asset'],
            'printOnly' => true,
        ], null);
    }

    public function qrView(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('assets/qr', [
            'pageTitle' => 'Asset Scan: ' . $bundle['asset']->asset_tag,
            'asset' => $bundle['asset'],
            'printOnly' => false,
        ], null);
    }

    public function qrViewByTag(string $tag): void
    {
        $tag = rawurldecode($tag);
        $asset = $this->db->fetch("SELECT id FROM assets WHERE asset_tag = ? AND deleted_at IS NULL", [$tag]);
        if (!$asset) {
            Session::flash('error', 'No active asset was found for tag "' . $tag . '".');
            $this->redirect('/qr/scan');
        }

        $this->redirect('/qr/asset/' . $asset->id);
    }

    public function scanner(): void
    {
        $this->view('assets/scanner', [
            'pageTitle' => 'Scan Asset QR',
        ], null);
    }

    public function reportIssue(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('assets/report', [
            'pageTitle' => 'Report Issue: ' . $bundle['asset']->asset_tag,
            'asset' => $bundle['asset'],
        ], null);
    }

    public function submitIssue(string $id): void
    {
        if (!CSRF::validate()) {
            Session::flash('error', 'Invalid request. Please try again.');
            $this->redirect('/qr/asset/' . $id . '/report');
        }

        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '' || $description === '') {
            Session::flash('error', 'Issue title and description are required.');
            $this->redirect('/qr/asset/' . $id . '/report');
        }

        $priority = $_POST['priority'] ?? 'medium';
        if (!in_array($priority, ['critical', 'high', 'medium', 'low'], true)) {
            $priority = 'medium';
        }

        $requesterId = $this->resolveReporterUserId(trim($_POST['reporter_email'] ?? ''));
        if ($requesterId <= 0) {
            Session::flash('error', 'No active user is available to own this ticket. Please contact the administrator.');
            $this->redirect('/qr/asset/' . $id . '/report');
        }

        $ticketNumber = $this->generateTicketNumber();
        $asset = $bundle['asset'];
        $reporterName = trim($_POST['reporter_name'] ?? '');
        $reporterEmail = trim($_POST['reporter_email'] ?? '');
        $reporterPhone = trim($_POST['reporter_phone'] ?? '');

        $ticketDescription = implode("\n", array_filter([
            'QR asset report submitted from mobile scan.',
            '',
            'Reporter: ' . ($reporterName ?: 'Not provided'),
            'Reporter email: ' . ($reporterEmail ?: 'Not provided'),
            'Reporter phone: ' . ($reporterPhone ?: 'Not provided'),
            '',
            'Asset: ' . $asset->asset_tag . ' - ' . $asset->name,
            'Serial: ' . ($asset->serial_number ?: '-'),
            'Location: ' . (trim(($asset->building_name ?? '') . ' ' . ($asset->floor_name ?? '') . ' ' . ($asset->room_number ?? '') . ' ' . ($asset->room_name ?? '')) ?: '-'),
            '',
            'Issue details:',
            $description,
        ]));

        try {
            $ticketId = $this->db->insert('tickets', [
                'ticket_number' => $ticketNumber,
                'title' => $title,
                'description' => $ticketDescription,
                'category_id' => $this->findTicketCategoryForAsset($asset),
                'priority' => $priority,
                'status' => 'new',
                'requester_id' => $requesterId,
                'department_id' => $asset->department_id ?: null,
                'building_id' => $asset->building_id ?: null,
                'floor_id' => $asset->floor_id ?: null,
                'room_id' => $asset->room_id ?: null,
                'asset_id' => $asset->id,
                'sla_due_at' => $this->slaDueAt($priority),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->insert('ticket_history', [
                'ticket_id' => $ticketId,
                'user_id' => $requesterId,
                'action' => 'created_from_qr_scan',
                'new_value' => $asset->asset_tag,
            ]);

            $this->db->insert('asset_history', [
                'asset_id' => $asset->id,
                'user_id' => $requesterId,
                'action' => 'qr_issue_reported',
                'description' => 'Issue reported from QR scan: ' . $ticketNumber,
            ]);

            $this->notifyAssetSupportTeam($ticketId, $ticketNumber, $asset->asset_tag);
            try {
                (new SlaMonitorService())->applySlaToTicket($ticketId);
            } catch (\Exception $e) {
                // SLA sync should not block QR issue reporting.
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to create ticket: ' . $e->getMessage());
            $this->redirect('/qr/asset/' . $id . '/report');
        }

        Session::flash('success', 'Issue reported successfully. Ticket ' . $ticketNumber . ' was created.');
        $this->redirect('/qr/asset/' . $id . '?ticket=' . urlencode($ticketNumber));
    }

    public function qrLabels(): void
    {
        $this->view('assets/labels', [
            'pageTitle' => 'Asset QR Labels',
            'assets' => $this->service->registry($this->assetFilters()),
        ], null);
    }

    public function dataList(): void
    {
        $this->json(['data' => $this->service->registry($this->assetFilters())]);
    }

    public function apiShow(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->json(['error' => 'Asset not found.'], 404);
        }

        $this->json($bundle);
    }

    public function apiStore(): void
    {
        $result = $this->service->create($this->requestData(), Session::userId());
        $this->json($result, $result['success'] ? 201 : 422);
    }

    public function apiUpdate(string $id): void
    {
        $result = $this->service->update((int)$id, $this->requestData(), Session::userId());
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiDelete(string $id): void
    {
        if (!$this->canManage()) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $result = $this->service->delete((int)$id, Session::userId());
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiAssign(string $id): void
    {
        if (!$this->canManage()) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $data = $this->requestData();
        $result = $this->service->assign((int)$id, (int)($data['user_id'] ?? 0), Session::userId(), trim($data['notes'] ?? ''));
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiReturn(string $id): void
    {
        if (!$this->canManage()) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $data = $this->requestData();
        $result = $this->service->returnAssignment((int)$id, Session::userId(), trim($data['notes'] ?? ''));
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiHistory(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->json(['error' => 'Asset not found.'], 404);
        }

        $this->json([
            'asset' => $bundle['asset'],
            'assignments' => $bundle['assignments'],
            'history' => $bundle['history'],
            'tickets' => $bundle['tickets'],
            'maintenance' => $bundle['maintenance'],
        ]);
    }

    public function apiWarranty(): void
    {
        $days = max(1, min(365, (int)($_GET['days'] ?? 90)));
        $this->json(['days' => $days, 'data' => $this->service->warrantyAlerts($days)]);
    }

    public function apiQR(string $id): void
    {
        $url = \App\Helpers\View::url('qr/asset/' . (int)$id);
        $payload = $this->service->qrPayload((int)$id, $url);
        if (!$payload) {
            $this->json(['error' => 'Asset not found.'], 404);
        }

        $this->json($payload);
    }

    private function assetFilters(): array
    {
        return [
            'status' => $_GET['status'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'assigned_user_id' => $_GET['assigned_user_id'] ?? '',
            'warranty' => $_GET['warranty'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];
    }

    private function requestData(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? array_merge($_POST, $json) : $_POST;
    }

    private function floors(): array
    {
        return $this->db->fetchAll("SELECT id, building_id, name FROM floors WHERE deleted_at IS NULL");
    }

    private function rooms(): array
    {
        return $this->db->fetchAll("SELECT r.id, r.name, r.room_number, f.building_id, r.floor_id FROM rooms r JOIN floors f ON r.floor_id = f.id WHERE r.deleted_at IS NULL");
    }

    private function canManage(): bool
    {
        $role = Session::get('role', 'staff');
        return in_array($role, ['manager', 'administrator', 'super_administrator', 'biomedical_engineer', 'technician'], true)
            || in_array('assets.edit', Session::get('permissions', []), true);
    }

    private function resolveReporterUserId(string $email): int
    {
        if (Session::isLoggedIn()) {
            return (int)Session::userId();
        }

        if ($email !== '') {
            $user = $this->db->fetch("SELECT id FROM users WHERE email = ? AND status='active' AND deleted_at IS NULL", [$email]);
            if ($user) {
                return (int)$user->id;
            }
        }

        $fallback = $this->db->fetch(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('administrator', 'super_administrator') AND u.status='active' AND u.deleted_at IS NULL
             ORDER BY FIELD(r.slug, 'administrator', 'super_administrator'), u.id ASC LIMIT 1"
        );

        if ($fallback) {
            return (int)$fallback->id;
        }

        return (int)$this->db->fetchColumn("SELECT id FROM users WHERE status='active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
    }

    private function generateTicketNumber(): string
    {
        $last = (int)$this->db->fetchColumn("SELECT MAX(id) FROM tickets");
        return 'HEMS-' . str_pad($last + 9400, 4, '0', STR_PAD_LEFT);
    }

    private function slaDueAt(string $priority): string
    {
        $minutes = $GLOBALS['appConfig']['sla_defaults'][$priority] ?? 480;
        return date('Y-m-d H:i:s', time() + ($minutes * 60));
    }

    private function findTicketCategoryForAsset(object $asset): ?int
    {
        $names = ['Medical Equipment', 'Hardware'];
        if (!empty($asset->category_name) && stripos($asset->category_name, 'IT') !== false) {
            $names = ['Hardware', 'Network'];
        }

        foreach ($names as $name) {
            $id = $this->db->fetchColumn(
                "SELECT id FROM ticket_categories WHERE name LIKE ? AND deleted_at IS NULL ORDER BY id ASC LIMIT 1",
                ['%' . $name . '%']
            );
            if ($id) {
                return (int)$id;
            }
        }

        return null;
    }

    private function notifyAssetSupportTeam(int $ticketId, string $ticketNumber, string $assetTag): void
    {
        $users = $this->db->fetchAll(
            "SELECT DISTINCT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician', 'biomedical_engineer', 'administrator', 'super_administrator')
             AND u.status='active' AND u.deleted_at IS NULL"
        );

        foreach ($users as $user) {
            try {
                (new NotificationService())->send(
                    (int)$user->id,
                    NOTIFY_TICKET_UPDATED,
                    'QR Asset Issue Reported',
                    "Ticket {$ticketNumber} was created from asset {$assetTag}.",
                    '/tickets/' . $ticketId
                );
            } catch (\Exception $e) {
                // Notification delivery should not block ticket creation.
            }
        }
    }
}
