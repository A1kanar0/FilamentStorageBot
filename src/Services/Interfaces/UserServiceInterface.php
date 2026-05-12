<?php
namespace App\Services\Interfaces;

use App\Models\User;

interface UserServiceInterface {
    public function authorize(int $telegramId, string $name): User;
}