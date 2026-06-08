<?php
namespace App\Jobs;

use App\Services\SlaMonitorService;

class SlaMonitorJob
{
    public function handle(int $limit = 500): array
    {
        return (new SlaMonitorService())->run($limit);
    }
}
