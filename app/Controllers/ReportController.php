<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\EnterpriseReportService;

class ReportController extends BaseController
{
    private EnterpriseReportService $service;

    public function __construct()
    {
        $this->service = new EnterpriseReportService();
    }

    public function index(): void
    {
        $this->view('reports/index', [
            'pageTitle' => 'Enterprise Reports',
        ] + $this->service->overview((int)Session::userId()));
    }

    public function tickets(): void
    {
        $this->renderReport('tickets');
    }

    public function assets(): void
    {
        $this->renderReport('assets');
    }

    public function sla(): void
    {
        $this->renderReport('sla');
    }

    public function maintenance(): void
    {
        $this->renderReport('maintenance');
    }

    public function inventory(): void
    {
        $this->renderReport('inventory');
    }

    public function userActivity(): void
    {
        $this->renderReport('user_activity');
    }

    public function api(string $type): void
    {
        try {
            $this->json(['success' => true] + $this->service->report($type, $this->service->filters($_GET)));
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function storeSchedule(): void
    {
        $result = $this->service->createSchedule((int)Session::userId(), $_POST);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/reports');
    }

    public function toggleSchedule(string $id): void
    {
        $this->service->toggleSchedule((int)$id, (int)Session::userId());
        Session::flash('success', 'Report schedule updated.');
        $this->redirect('/reports');
    }

    public function export(string $type): void
    {
        try {
            $format = $_GET['format'] ?? 'csv';
            $export = $this->service->export($type, $format, $this->service->filters($_GET));

            header('Content-Type: ' . $export['content_type']);
            header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $export['content'];
            exit;
        } catch (\InvalidArgumentException $e) {
            $this->abort(404);
        }
    }

    private function renderReport(string $type): void
    {
        $report = $this->service->report($type, $this->service->filters($_GET));
        $this->view('reports/enterprise', [
            'pageTitle' => $report['title'],
            'report' => $report,
            'filterOptions' => $this->service->filterOptions(),
            'reportTypes' => $this->service->reportTypes(),
        ]);
    }
}
