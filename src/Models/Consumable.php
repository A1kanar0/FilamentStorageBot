<?php

namespace App\Models;

class Consumable
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $type,
        private float $initialAmount,
        private float $currentAmount,
        private string $unit = 'g',
        private ?string $color = null,
        private ?string $brand = null,
        private ?string $createdAt = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getInitialAmount(): float { return $this->initialAmount; }
    public function getCurrentAmount(): float { return $this->currentAmount; }
    public function getUnit(): string { return $this->unit; }
    public function getColor(): ?string { return $this->color; }
    public function getBrand(): ?string { return $this->brand; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

    public function deductAmount(float $amount): void
    {
        $this->currentAmount -= $amount;
        if ($this->currentAmount < 0) {
            $this->currentAmount = 0;
        }
    }
}