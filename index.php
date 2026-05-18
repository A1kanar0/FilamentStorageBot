<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

use App\AppContainer;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - " . $content . PHP_EOL, FILE_APPEND);

if (!$update) {
    exit;
}

try {
    // Створюємо контейнер залежностей
    $container = new AppContainer();

    // Отримуємо сконфігурований контролер без ручного створення сервісів
    $controller = $container->getBotController();
    $controller->handleUpdate($update);

} catch (\Throwable $e) {
    file_put_contents('bot_errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL, FILE_APPEND);
}