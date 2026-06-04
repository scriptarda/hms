<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Helpers\Database;
use App\Services\ServiceRequestService;

class ServiceRequestController extends BaseController
{
    private Database $db;
    private ServiceRequestService $service;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->service = new ServiceRequestService();
    }

    public function index(): void
    {
        $role = Session::get('role', 'staff');
        $userId = Session::userId();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];

        $requests = $this->service->getRequests($role, $userId, $filters);
        $catalogItems = $this->service->catalog();

        $metrics = [
            'total' => count($requests),
            'pending' => count(array_filter($requests, fn($r) => $r->status === 'pending_approval')),
            'fulfilling' => count(array_filter($requests, fn($r) => $r->status === 'fulfilling' || $r->fulfillment_status === 'in_progress')),
            'completed' => count(array_filter($requests, fn($r) => $r->status === 'completed')),
        ];

        $this->view('service-requests/index', [
            'pageTitle' => 'Service Requests',
            'requests' => $requests,
            'catalogItems' => $catalogItems,
            'filters' => $filters,
            'metrics' => $metrics,
        ]);
    }

    public function catalog(): void
    {
        $this->view('service-requests/catalog', [
            'pageTitle' => 'Service Catalog',
            'catalogItems' => $this->service->catalog(),
        ]);
    }

    public function create(string $type): void
    {
        $item = $this->service->getCatalogItem($type);
        if (!$item) {
            $this->abort(404);
        }

        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");

        $this->view('service-requests/create', [
            'pageTitle' => $item->name,
            'type' => $item->type,
            'catalogItem' => $item,
            'departments' => $departments,
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('title')->required('type')->required('priority')->required('department_id');
        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/service-requests/catalog');
        }

        $result = $this->service->create($_POST, Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $type = $this->service->normalizeType($_POST['type'] ?? '');
            $this->redirect($type ? '/service-requests/create/' . $type : '/service-requests/catalog');
        }

        Session::flash('success', 'Service request submitted successfully.');
        $this->redirect('/service-requests/' . $result['id']);
    }

    public function show(string $id): void
    {
        $bundle = $this->service->getRequestBundle((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        if (!$this->canView($bundle['request'])) {
            $this->abort(403, 'You cannot view this service request.');
        }

        $this->view('service-requests/show', [
            'pageTitle' => 'Request ' . $bundle['request']->request_number,
            'request' => $bundle['request'],
            'approvals' => $bundle['approvals'],
            'fieldValues' => $bundle['fieldValues'],
            'activity' => $bundle['activity'],
            'catalogItem' => $bundle['catalogItem'],
        ]);
    }

    public function approve(string $id): void
    {
        $result = $this->service->approve(
            (int)$id,
            Session::userId(),
            Session::get('role', 'staff'),
            Session::get('permissions', []),
            trim($_POST['comments'] ?? '')
        );

        $this->finishWorkflowAction($result, '/service-requests/' . $id);
    }

    public function reject(string $id): void
    {
        $result = $this->service->reject(
            (int)$id,
            Session::userId(),
            Session::get('role', 'staff'),
            Session::get('permissions', []),
            trim($_POST['comments'] ?? '')
        );

        $this->finishWorkflowAction($result, '/service-requests/' . $id, 'warning');
    }

    public function startFulfillment(string $id): void
    {
        $result = $this->service->startFulfillment((int)$id, Session::userId(), Session::get('role', 'staff'));
        $this->finishWorkflowAction($result, '/service-requests/' . $id);
    }

    public function completeFulfillment(string $id): void
    {
        $result = $this->service->completeFulfillment(
            (int)$id,
            Session::userId(),
            Session::get('role', 'staff'),
            trim($_POST['completion_notes'] ?? '')
        );
        $this->finishWorkflowAction($result, '/service-requests/' . $id);
    }

    public function cancel(string $id): void
    {
        $result = $this->service->cancel(
            (int)$id,
            Session::userId(),
            Session::get('role', 'staff'),
            trim($_POST['cancel_reason'] ?? '')
        );
        $this->finishWorkflowAction($result, '/service-requests/' . $id, 'warning');
    }

    public function catalogData(): void
    {
        $this->json(['data' => $this->service->catalog()]);
    }

    public function formSchema(string $type): void
    {
        $item = $this->service->getCatalogItem($type);
        if (!$item) {
            $this->json(['error' => 'Catalog item not found.'], 404);
        }

        $this->json([
            'type' => $item->type,
            'name' => $item->name,
            'default_priority' => $item->default_priority,
            'approval_mode' => $item->approval_mode,
            'fields' => $item->schema,
        ]);
    }

    public function dataList(): void
    {
        $this->json([
            'data' => $this->service->getRequests(
                Session::get('role', 'staff'),
                Session::userId(),
                [
                    'status' => $_GET['status'] ?? '',
                    'type' => $_GET['type'] ?? '',
                    'search' => trim($_GET['search'] ?? ''),
                ]
            )
        ]);
    }

    public function tracking(string $id): void
    {
        $bundle = $this->service->getRequestBundle((int)$id);
        if (!$bundle) {
            $this->json(['error' => 'Request not found.'], 404);
        }

        if (!$this->canView($bundle['request'])) {
            $this->json(['error' => 'Forbidden.'], 403);
        }

        $this->json([
            'request' => $bundle['request'],
            'fields' => $bundle['fieldValues'],
            'approvals' => $bundle['approvals'],
            'activity' => $bundle['activity'],
        ]);
    }

    private function canView(object $request): bool
    {
        $role = Session::get('role', 'staff');
        if (in_array($role, ['manager', 'administrator', 'super_administrator'], true)) {
            return true;
        }

        return (int)$request->requester_id === Session::userId();
    }

    private function finishWorkflowAction(array $result, string $fallback, string $successFlash = 'success'): void
    {
        if ($this->isAjax()) {
            $this->json($result, $result['success'] ? 200 : 422);
        }

        Session::flash($result['success'] ? $successFlash : 'error', $result['message']);
        $this->redirect($fallback);
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
