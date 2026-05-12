<?php

namespace App\Database;

use PDO;
use PDOException;
use Exception;

class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = DB_CONFIG;

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                $options
            );
        } catch (PDOException $e) {
            throw new Exception("Помилка підключення до БД: " . $e->getMessage());
        }
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Десеріалізація Singleton заборонена.");
    }

    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}