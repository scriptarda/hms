<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\Database;
use App\Services\AssetService;

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
}
