<?php

namespace App\Repositories\Interfaces;

use App\Models\Consumable;

interface ConsumableRepositoryInterface
{
    public function findById(int $id): ?Consumable;
    public function getAll(): array;
    public function update(Consumable $consumable): bool;
    public function create(Consumable $consumable): bool;
    public function exists(string $type, ?string $brand, ?string $name, ?string $color): bool;
}