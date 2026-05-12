<?php

namespace App\Services;

interface ConsumableServiceInterface
{
    public function getConsumablesList(): array;
    public function deductMaterial(int $consumableId, float $amount): void;
}