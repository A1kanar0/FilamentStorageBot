<?php

namespace App\Repositories;

use App\Models\Consumable;
use App\Database\DatabaseConnection;
use App\Repositories\Interfaces\ConsumableRepositoryInterface;
use PDO;

class ConsumableRepository implements ConsumableRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function findById(int $id): ?Consumable
    {
        $stmt = $this->db->prepare("SELECT * FROM consumables WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Consumable(
            $row['id'],
            $row['name'],
            $row['type'],
            (float) $row['initial_amount'],
            (float) $row['current_amount'],
            $row['unit'],
            $row['color'],
            $row['brand'],
            $row['created_at']
        );
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM consumables ORDER BY name ASC");
        $consumables = [];

        while ($row = $stmt->fetch()) {
            $consumables[] = new Consumable(
                $row['id'],
                $row['name'],
                $row['type'],
                (float) $row['initial_amount'],
                (float) $row['current_amount'],
                $row['unit'],
                $row['color'],
                $row['brand'],
                $row['created_at']
            );
        }

        return $consumables;
    }

    public function update(Consumable $consumable): bool
    {
        $stmt = $this->db->prepare("
            UPDATE consumables 
            SET current_amount = :current_amount 
            WHERE id = :id
        ");

        return $stmt->execute([
            'current_amount' => $consumable->getCurrentAmount(),
            'id' => $consumable->getId()
        ]);
    }

    public function create(Consumable $consumable): bool
    {
        $stmt = $this->db->prepare("
        INSERT INTO consumables (name, type, initial_amount, current_amount, unit, color, brand) 
        VALUES (:name, :type, :initial_amount, :current_amount, :unit, :color, :brand)
    ");

        return $stmt->execute([
            'name'           => $consumable->getName(),
            'type'           => $consumable->getType(),
            'initial_amount' => $consumable->getInitialAmount(),
            'current_amount' => $consumable->getCurrentAmount(),
            'unit'           => $consumable->getUnit(),
            'color'          => $consumable->getColor(),
            'brand'          => $consumable->getBrand()
        ]);
    }
}