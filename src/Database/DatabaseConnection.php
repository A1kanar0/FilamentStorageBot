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
        $host = '127.0.0.1';
        $port = '8888';
        $db   = 'filament_db';
        $user = 'root';
        $pass = 'root';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
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