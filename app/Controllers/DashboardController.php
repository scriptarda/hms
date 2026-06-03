<?php
namespace App\Controllers;

use App\Helpers\BaseController;
use App\Helpers\Session;
use App\Services\DashboardService;

class DashboardController extends BaseController
{
    private DashboardService $dashService;

    public function __construct()
    {
        $this->dashService = new DashboardService();
    }

    public function index(): void
    {
        $role = Session::get('role', 'staff');
        $userId = Session::userId();

        $data = ['pageTitle' => 'Dashboard'];

        switch ($role) {
            case 'technician':
            case 'biomedical_engineer':
                $data['stats'] = $this->dashService->getTechStats($userId);
                $data['myTickets'] = $this->dashService->getMyTickets($userId);
                $data['activity'] = $this->dashService->getRecentActivity(5);
                $this->view('dashboard/technician', $data);
                break;

            case 'manager':
            case 'administrator':
            case 'super_administrator':
                $data['stats'] = $this->dashService->getManagementStats();
                $data['statusCounts'] = $this->dashService->getStatusCounts();
                $data['trends'] = $this->dashService->getMonthlyTrends();
                $data['activity'] = $this->dashService->getRecentActivity(5);
                $this->view('dashboard/management', $data);
                break;

            default: // staff, nurse, doctor
                $data['stats'] = $this->dashService->getStaffStats($userId);
                $data['statusCounts'] = $this->dashService->getStatusCounts();
                $data['activity'] = $this->dashService->getRecentActivity(5);
                $this->view('dashboard/staff', $data);
                break;
        }
    }

    public function getData(): void
    {
        $this->json([
            'statusCounts' => $this->dashService->getStatusCounts(),
            'trends' => $this->dashService->getMonthlyTrends(),
        ]);
    }
}
