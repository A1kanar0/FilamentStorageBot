<?php

namespace App\Services\Interfaces;

interface ConsumableServiceInterface
{
    public function getConsumablesList(): array;
    public function deductMaterial(int $consumableId, float $amount, int $userId): void;
    public function createConsumable(array $data): void;
    public function addStock(int $consumableId, float $amount): void;
    public function getConsumable(int $id): ?\App\Models\Consumable;
    public function getStockStatus(float $currentAmount): string;
}