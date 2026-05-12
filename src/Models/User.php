<?php

namespace App\Models;

class User
{
    public function __construct(
        private ?int $id,
        private int $telegramId,
        private ?string $fullName = null,
        private ?string $createdAt = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
}