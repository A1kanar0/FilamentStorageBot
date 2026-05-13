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
            $row['category'],
            $row['type'],
            (float) $row['initial_amount'],
            (float) $row['current_amount'],
            $row['unit'],
            $row['color'],
            $row['brand'],
            $row['created_at']
        );
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM consumables WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM consumables WHERE current_amount > 0 ORDER BY name ASC");
        $consumables = [];

        while ($row = $stmt->fetch()) {
            $consumables[] = new Consumable(
                $row['id'],
                $row['name'],
                $row['category'],
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
        INSERT INTO consumables (name, category, type, initial_amount, current_amount, unit, color, brand) 
        VALUES (:name, :category, :type, :initial_amount, :current_amount, :unit, :color, :brand)
    ");

        return $stmt->execute([
            'name'           => $consumable->getName(),
            'category'       => $consumable->getCategory(),
            'type'           => $consumable->getType(),
            'initial_amount' => $consumable->getInitialAmount(),
            'current_amount' => $consumable->getCurrentAmount(),
            'unit'           => $consumable->getUnit(),
            'color'          => $consumable->getColor(),
            'brand'          => $consumable->getBrand()
        ]);
    }
    public function exists(string $type, ?string $brand, ?string $name, ?string $color): bool
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM consumables 
        WHERE type = :type 
          AND (brand = :brand OR (brand IS NULL AND :brand_null IS NULL))
          AND (name = :name OR (name IS NULL AND :name_null IS NULL))
          AND (color = :color OR (color IS NULL AND :color_null IS NULL))
    ");

        $stmt->execute([
            'type' => $type,
            'brand' => $brand,
            'brand_null' => $brand,
            'name' => $name,
            'name_null' => $name,
            'color' => $color,
            'color_null' => $color
        ]);

        return $stmt->fetchColumn() > 0;
    }

    public function findByAttributes(string $type, ?string $brand, ?string $name, ?string $color): ?Consumable
    {
        $stmt = $this->db->prepare("
            SELECT * FROM consumables 
            WHERE type = :type 
              AND (brand = :brand OR (brand IS NULL AND :brand_null IS NULL))
              AND (name = :name OR (name IS NULL AND :name_null IS NULL))
              AND (color = :color OR (color IS NULL AND :color_null IS NULL))
            LIMIT 1
        ");

        $stmt->execute([
            'type' => $type,
            'brand' => $brand,
            'brand_null' => $brand,
            'name' => $name,
            'name_null' => $name,
            'color' => $color,
            'color_null' => $color
        ]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Consumable(
            $row['id'], $row['name'], $row['category'], $row['type'],
            (float) $row['initial_amount'], (float) $row['current_amount'],
            $row['unit'], $row['color'], $row['brand'], $row['created_at']
        );
    }

    public function restoreMaterial(int $id, float $newAmount): bool
    {
        $stmt = $this->db->prepare("
            UPDATE consumables 
            SET current_amount = :current_amount, 
                initial_amount = :initial_amount 
            WHERE id = :id
        ");

        return $stmt->execute([
            'current_amount' => $newAmount,
            'initial_amount' => $newAmount,
            'id'             => $id
        ]);
    }
}