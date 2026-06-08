<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\SlaMonitorService;

class SlaController extends BaseController
{
    private SlaMonitorService $service;

    public function __construct()
    {
        $this->service = new SlaMonitorService();
    }

    public function index(): void
    {
        $this->view('sla/index', [
            'pageTitle' => 'SLA Management',
        ] + $this->service->dashboard());
    }

    public function storeRule(): void
    {
        $result = $this->service->createRule($_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/sla');
    }

    public function updateRule(string $id): void
    {
        $result = $this->service->updateRule((int)$id, $_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/sla');
    }

    public function runMonitor(): void
    {
        $result = $this->service->run((int)($_POST['limit'] ?? 500));
        Session::flash(
            $result['success'] ? 'success' : 'error',
            "SLA monitor checked {$result['checked']} tickets, updated {$result['updated']}, found {$result['warnings']} warnings, {$result['breaches']} breaches, and {$result['escalations']} escalations."
        );
        $this->redirect('/sla');
    }

    public function apiMetrics(): void
    {
        $this->json(['success' => true] + $this->service->dashboard());
    }

    public function apiRules(): void
    {
        $this->json(['success' => true, 'data' => $this->service->rules()]);
    }

    public function apiRunMonitor(): void
    {
        $this->json($this->service->run((int)($_POST['limit'] ?? 500)));
    }
}
