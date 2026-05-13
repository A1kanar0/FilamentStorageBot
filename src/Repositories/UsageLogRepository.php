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
        $stmt = $this->db->prepare("
        INSERT INTO usage_logs (consumable_id, user_id, spent_amount)
        VALUES (:cid, :uid, :amt)
    ");
        return $stmt->execute([
            'cid' => $log->getConsumableId(),
            'uid' => $log->getUserId(),
            'amt' => $log->getSpentAmount()
        ]);
    }

    public function getLatestLogs(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT 
                ul.*, 
                c.type, c.brand, c.color, c.name as consumable_name,
                u.full_name as user_name
            FROM usage_logs ul
            JOIN consumables c ON ul.consumable_id = c.id
            JOIN users u ON ul.user_id = u.telegram_id 
            ORDER BY ul.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}