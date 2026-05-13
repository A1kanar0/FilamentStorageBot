<?php

namespace App\Services;

use App\Models\UsageLog;
use App\Models\Consumable;
use App\Repositories\Interfaces\ConsumableRepositoryInterface;
use App\Repositories\Interfaces\UsageLogRepositoryInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use Exception;

class ConsumableService implements ConsumableServiceInterface
{
    public const MIN_THRESHOLD = 100.0;

    public function __construct(
        private ConsumableRepositoryInterface $consumableRepository,
        private UsageLogRepositoryInterface $usageLogRepository
    ) {}

    public function getConsumablesList(): array
    {
        return $this->consumableRepository->getAll();
    }

    public function deductMaterial(int $consumableId, float $amount, int $userId): void
    {
        if ($amount <= 0) {
            throw new Exception("Сума списання має бути більшою за нуль.");
        }

        $consumable = $this->consumableRepository->findById($consumableId);
        if (!$consumable) {
            throw new Exception("Матеріал не знайдено.");
        }

        if ($consumable->getCurrentAmount() < $amount) {
            throw new Exception("Недостатньо матеріалу на залишку.");
        }

        $log = new UsageLog(
            null,
            $consumableId,
            $userId,
            $amount
        );

        if (!$this->usageLogRepository->save($log)) {
            throw new Exception("Не вдалося записати дію в журнал.");
        }

        $consumable->deductAmount($amount);

        if (!$this->consumableRepository->update($consumable)) {
            throw new Exception("Не вдалося оновити залишок у базі.");
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

        $existingMaterial = $this->consumableRepository->findByAttributes(
            $data['type'],
            $data['brand'] ?? null,
            $data['name'] ?? null,
            $data['color'] ?? null
        );

        if ($existingMaterial) {
            if ($existingMaterial->getCurrentAmount() <= 0) {
                if (!$this->consumableRepository->restoreMaterial($existingMaterial->getId(), $weight)) {
                    throw new Exception("Помилка при відновленні матеріалу в БД.");
                }
                return;
            } else {
                throw new Exception("Цей матеріал вже зареєстрований. Використовуйте 'Поповнити залишок'.");
            }
        }

        $consumable = new Consumable(
            null,
            $data['name'] ?? null,
            $data['category'],
            $data['type'] ?? 'resin',
            $weight,
            $weight,
            $data['unit'] ?? 'g',
            $data['color'] ?? null,
            $data['brand'] ?? null
        );

        if (!$this->consumableRepository->create($consumable)) {
            throw new Exception("Помилка при збереженні матеріалу в БД.");
        }
    }

    public function addStock(int $consumableId, float $amount): void
    {
        if ($amount <= 0) {
            throw new Exception("Сума поповнення має бути більшою за нуль.");
        }

        $consumable = $this->consumableRepository->findById($consumableId);
        if (!$consumable) {
            throw new Exception("Матеріал не знайдено.");
        }

        $newAmount = $consumable->getCurrentAmount() + $amount;
        $consumable->setCurrentAmount($newAmount);

        if (!$this->consumableRepository->update($consumable)) {
            throw new Exception("Не вдалося оновити базу даних.");
        }
    }

    public function getConsumable(int $id): ?Consumable
    {
        return $this->consumableRepository->findById($id);
    }

    public function getStockStatus(float $currentAmount): string
    {
        if ($currentAmount <= 0) {
            return 'EXHAUSTED';
        }

        if ($currentAmount <= self::MIN_THRESHOLD) {
            return 'LOW_STOCK';
        }

        return 'NORMAL';
    }
}