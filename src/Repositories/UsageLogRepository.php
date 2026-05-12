<?php
namespace App\Repositories;

use App\Models\UsageLog;
use App\Database\DatabaseConnection;
use App\Repositories\Interfaces\UsageLogRepositoryInterface;
use PDO;

class UsageLogRepository implements UsageLogRepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function save(UsageLog $log): bool {
        $stmt = $this->db->prepare("INSERT INTO usage_logs (consumable_id, user_id, spent_amount) VALUES (:cid, :uid, :amt)");
        return $stmt->execute(['cid' => $log->getConsumableId(), 'uid' => $log->getUserId(), 'amt' => $log->getSpentAmount()]);
    }
}