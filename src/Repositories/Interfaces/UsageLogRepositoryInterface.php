<?php
namespace App\Repositories\Interfaces;

use App\Models\UsageLog;

interface UsageLogRepositoryInterface {
    public function save(UsageLog $log): bool;
}