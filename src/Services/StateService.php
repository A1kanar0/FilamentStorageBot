<?php
namespace App\Services;

use App\Models\UserState;
use App\Repositories\Interfaces\UserStateRepositoryInterface;
use App\Services\Interfaces\StateServiceInterface;

class StateService implements StateServiceInterface {
    public function __construct(private UserStateRepositoryInterface $stateRepository) {}

    public function getCurrentState(int $userId): string {
        $state = $this->stateRepository->getByUserId($userId);
        return $state ? $state->getState() : 'IDLE';
    }

    public function setNextState(int $userId, string $state, ?array $context = null): void {
        $ctxJson = $context ? json_encode($context) : null;
        $userState = new UserState($userId, $state, $ctxJson);
        $this->stateRepository->set($userState);
    }

    public function clearState(int $userId): void {
        $this->stateRepository->delete($userId);
    }
}