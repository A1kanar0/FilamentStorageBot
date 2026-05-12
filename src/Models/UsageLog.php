<?php

namespace App\Models;

class UsageLog
{
    public function __construct(
        private ?int $id,
        private int $consumableId,
        private int $userId,
        private float $spentAmount,
        private ?string $createdAt = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getConsumableId(): int { return $this->consumableId; }
    public function getUserId(): int { return $this->userId; }
    public function getSpentAmount(): float { return $this->spentAmount; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
}