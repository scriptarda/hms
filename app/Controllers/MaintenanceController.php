<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Helpers\View;
use App\Services\MaintenanceService;

class MaintenanceController extends BaseController
{
    private MaintenanceService $service;

    public function __construct()
    {
        $this->service = new MaintenanceService();
    }

    public function index(): void
    {
        $this->view('maintenance/index', array_merge(
            ['pageTitle' => 'Maintenance Dashboard'],
            $this->service->dashboard((int)Session::userId())
        ));
    }

    public function workOrders(): void
    {
        $this->view('maintenance/work_orders', [
            'pageTitle' => 'Maintenance Work Orders',
            'tasks' => $this->service->workOrders($this->filters()),
            'filters' => $this->filters(),
            'formData' => $this->service->formData(),
        ]);
    }

    public function calendar(): void
    {
        $this->view('maintenance/calendar', [
            'pageTitle' => 'Maintenance Calendar',
        ]);
    }

    public function history(): void
    {
        $this->view('maintenance/history', [
            'pageTitle' => 'Maintenance History',
            'tasks' => $this->service->history($this->filters()),
            'filters' => $this->filters(),
            'formData' => $this->service->formData(),
        ]);
    }

    public function queue(): void
    {
        $filters = [
            'scope' => $_GET['scope'] ?? 'team',
            'priority' => $_GET['priority'] ?? '',
        ];

        $this->view('maintenance/queue', [
            'pageTitle' => 'Technician Work Queue',
            'tasks' => $this->service->queue((int)Session::userId(), $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->view('maintenance/create', array_merge(
            ['pageTitle' => 'Schedule Maintenance'],
            $this->service->formData()
        ));
    }

    public function store(): void
    {
        $result = $this->service->create($_POST, (int)Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/maintenance/create');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/maintenance/' . $result['id']);
    }

    public function show(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $bundle['pageTitle'] = 'Work Order ' . ($bundle['task']->wo_number ?? ('WO-' . $bundle['task']->id));
        $this->view('maintenance/show', $bundle);
    }

    public function edit(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->abort(404);
        }

        $this->view('maintenance/edit', array_merge(
            [
                'pageTitle' => 'Edit Work Order',
                'task' => $bundle['task'],
                'checklist' => $bundle['checklist'],
            ],
            $this->service->formData()
        ));
    }

    public function update(string $id): void
    {
        $result = $this->service->update((int)$id, $_POST, (int)Session::userId());
        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/maintenance/' . $id . '/edit');
        }

        Session::flash('success', $result['message']);
        $this->redirect('/maintenance/' . $id);
    }

    public function start(string $id): void
    {
        $result = $this->service->start((int)$id, (int)Session::userId(), trim($_POST['notes'] ?? ''));
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/maintenance/' . $id);
    }

    public function complete(string $id): void
    {
        $result = $this->service->complete((int)$id, $_POST, (int)Session::userId());
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/maintenance/' . $id);
    }

    public function cancel(string $id): void
    {
        $result = $this->service->cancel((int)$id, (int)Session::userId(), trim($_POST['reason'] ?? ''));
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/maintenance/' . $id);
    }

    public function events(): void
    {
        $this->apiCalendarEvents();
    }

    public function apiDashboard(): void
    {
        $this->json($this->service->dashboard((int)Session::userId()));
    }

    public function apiWorkOrders(): void
    {
        $this->json(['data' => $this->service->workOrders($this->filters())]);
    }

    public function apiShow(string $id): void
    {
        $bundle = $this->service->detail((int)$id);
        if (!$bundle) {
            $this->json(['success' => false, 'message' => 'Work order not found.'], 404);
        }

        $this->json(['success' => true] + $bundle);
    }

    public function apiStore(): void
    {
        $result = $this->service->create($_POST, (int)Session::userId());
        $this->json($result, $result['success'] ? 201 : 422);
    }

    public function apiUpdate(string $id): void
    {
        $result = $this->service->update((int)$id, $_POST, (int)Session::userId());
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiStart(string $id): void
    {
        $result = $this->service->start((int)$id, (int)Session::userId(), trim($_POST['notes'] ?? ''));
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiComplete(string $id): void
    {
        $result = $this->service->complete((int)$id, $_POST, (int)Session::userId());
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiCancel(string $id): void
    {
        $result = $this->service->cancel((int)$id, (int)Session::userId(), trim($_POST['reason'] ?? ''));
        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function apiCalendarEvents(): void
    {
        $this->json($this->service->calendarEvents(
            $_GET['start'] ?? null,
            $_GET['end'] ?? null,
            rtrim(View::url(''), '/')
        ));
    }

    public function apiHistory(): void
    {
        $this->json(['data' => $this->service->history($this->filters())]);
    }

    public function apiQueue(): void
    {
        $this->json(['data' => $this->service->queue((int)Session::userId(), [
            'scope' => $_GET['scope'] ?? 'team',
            'priority' => $_GET['priority'] ?? '',
        ])]);
    }

    public function apiSchedules(): void
    {
        $this->json(['data' => $this->service->schedules([
            'active' => $_GET['active'] ?? '',
            'due_within_days' => $_GET['due_within_days'] ?? '',
        ])]);
    }

    public function apiStoreSchedule(): void
    {
        $result = $this->service->createSchedule($_POST, (int)Session::userId());
        $this->json($result, $result['success'] ? 201 : 422);
    }

    public function apiGenerateSchedule(string $id): void
    {
        $result = $this->service->generateFromSchedule((int)$id, (int)Session::userId());
        $this->json($result, $result['success'] ? 200 : 422);
    }

    private function filters(): array
    {
        return [
            'status' => $_GET['status'] ?? '',
            'type' => $_GET['type'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'asset_id' => $_GET['asset_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];
    }
}
