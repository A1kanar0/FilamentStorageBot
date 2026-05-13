<?php

namespace App\Services;

use App\Repositories\Interfaces\ConsumableRepositoryInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
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
            throw new \Exception("Сума списання має бути більшою за нуль.");
        }

        $consumable = $this->consumableRepository->findById($consumableId);

        if (!$consumable) {
            throw new \Exception("Матеріал не знайдено.");
        }

        if ($consumable->getCurrentAmount() < $amount) {
            throw new \Exception("Недостатньо матеріалу на залишку (є лише {$consumable->getCurrentAmount()}г).");
        }

        $consumable->deductAmount($amount);

        if (!$this->consumableRepository->update($consumable)) {
            throw new \Exception("Не вдалося оновити залишок у базі.");
        }
    }

    public function createConsumable(array $data): void
    {
        $allowedCategories = ['filament', 'resin'];
        $allowedTypes = ['PLA', 'PETG', 'TPU', 'PLA+', 'PLA High-speed', 'PETG High-speed', 'ABS'];


        if (!in_array($data['category'], $allowedCategories)) {
            throw new Exception("Невідома категорія матеріалу.");
        }

        if ($data['category'] === 'filament' && !in_array($data['type'], $allowedTypes)) {
            throw new Exception("Тип пластику '{$data['type']}' не підтримується.");
        }

        $weight = (float)($data['initial_amount'] ?? 0);
        if ($weight <= 0) {
            throw new Exception("Початкова вага має бути більшою за нуль.");
        }

        $consumable = new \App\Models\Consumable(
            null,
            $data['name'] ?? null,
            $data['type'] ?? 'resin',
            $weight,
            $weight,
            $data['unit'] ?? 'g',
            $data['color'] ?? null,
            $data['brand'] ?? null
        );

        if (!$this->consumableRepository->create($consumable)) {
            throw new Exception("Помилка при збереженні в БД.");
        }
    }
}