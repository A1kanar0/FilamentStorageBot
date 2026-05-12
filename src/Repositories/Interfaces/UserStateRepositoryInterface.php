<?php
namespace App\Repositories\Interfaces;

use App\Models\UserState;

interface UserStateRepositoryInterface {
    public function getByUserId(int $userId): ?UserState;
    public function set(UserState $state): bool;
    public function delete(int $userId): bool;
}