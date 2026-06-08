<?php
namespace App\Jobs;

use App\Services\EnterpriseReportService;

class ReportSchedulerJob
{
    public function handle(int $limit = 25): array
    {
        return (new EnterpriseReportService())->runDueSchedules($limit);
    }
}
