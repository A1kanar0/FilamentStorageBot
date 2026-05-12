<?php
namespace App\Repositories;

use App\Models\UserState;
use App\Database\DatabaseConnection;
use App\Repositories\Interfaces\UserStateRepositoryInterface;
use PDO;

class UserStateRepository implements UserStateRepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function getByUserId(int $userId): ?UserState {
        $stmt = $this->db->prepare("SELECT * FROM user_states WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return $row ? new UserState($row['user_id'], $row['state'], $row['context_data'], $row['updated_at']) : null;
    }

    public function set(UserState $state): bool {
        $stmt = $this->db->prepare("REPLACE INTO user_states (user_id, state, context_data) VALUES (:id, :st, :ctx)");
        return $stmt->execute(['id' => $state->getUserId(), 'st' => $state->getState(), 'ctx' => $state->getContextData()]);
    }

    public function delete(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM user_states WHERE user_id = :id");
        return $stmt->execute(['id' => $userId]);
    }
}