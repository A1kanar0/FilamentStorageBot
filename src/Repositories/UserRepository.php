<?php
namespace App\Repositories;

use App\Models\User;
use App\Database\DatabaseConnection;
use App\Repositories\Interfaces\UserRepositoryInterface;
use PDO;

class UserRepository implements UserRepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function findByTelegramId(int $telegramId): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE telegram_id = :tid");
        $stmt->execute(['tid' => $telegramId]);
        $row = $stmt->fetch();
        return $row ? new User($row['id'], $row['telegram_id'], $row['full_name'], $row['created_at']) : null;
    }

    public function create(User $user): bool {
        $stmt = $this->db->prepare("INSERT INTO users (telegram_id, full_name) VALUES (:tid, :name)");
        return $stmt->execute(['tid' => $user->getTelegramId(), 'name' => $user->getFullName()]);
    }
}