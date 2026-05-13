<?php
namespace App\Services\Interfaces;

interface StateServiceInterface {
    public function getCurrentState(int $userId): string;
    public function getCurrentStateFull(int $userId): ?object;
    public function setNextState(int $userId, string $state, ?array $context = null): void;
    public function clearState(int $userId): void;
}