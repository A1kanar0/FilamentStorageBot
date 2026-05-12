<?php

namespace App\Models;

class UserState
{
    public function __construct(
        private int $userId,
        private string $state,
        private ?string $contextData = null,
        private ?string $updatedAt = null
    ) {}

    public function getUserId(): int { return $this->userId; }
    public function getState(): string { return $this->state; }
    public function getContextData(): ?string { return $this->contextData; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function changeState(string $newState, ?string $newContext = null): void
    {
        $this->state = $newState;
        if ($newContext !== null) {
            $this->contextData = $newContext;
        }
    }
}