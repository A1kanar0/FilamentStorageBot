<?php
namespace App\Repositories\Interfaces;

use App\Models\UsageLog;

interface UsageLogRepositoryInterface {
    public function save(UsageLog $log): bool;
    public function getLatestLogs(int $limit = 10): array;
}