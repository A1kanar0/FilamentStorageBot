<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;

class UserService implements UserServiceInterface {
    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function authorize(int $telegramId, string $name): User {
        $user = $this->userRepository->findByTelegramId($telegramId);
        if (!$user) {
            $user = new User(null, $telegramId, $name);
            $this->userRepository->create($user);
            return $this->userRepository->findByTelegramId($telegramId);
        }
        return $user;
    }
}