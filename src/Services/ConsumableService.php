<?php

namespace App\Services;

use App\Repositories\ConsumableRepositoryInterface;
use Exception;

class ConsumableService implements ConsumableServiceInterface
{
    public function __construct(
        private ConsumableRepositoryInterface $consumableRepository
    ) {}

    public function getConsumablesList(): array
    {
        return $this->consumableRepository->getAll();
    }

    public function deductMaterial(int $consumableId, float $amount): void
    {
        if ($amount <= 0) {
            throw new Exception("Сума списання має бути більшою за нуль.");
        }

        $consumable = $this->consumableRepository->findById($consumableId);

        if (!$consumable) {
            throw new Exception("Матеріал не знайдено.");
        }

        $consumable->deductAmount($amount);

        $this->consumableRepository->update($consumable);
    }
}