<?php
namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface {
    public function findByTelegramId(int $telegramId): ?User;
    public function create(User $user): bool;
}