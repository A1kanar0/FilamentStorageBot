<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

use App\Utils\TelegramBot;
use App\Controllers\BotController;
use App\Services\UserService;
use App\Repositories\UserRepository;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - " . $content . PHP_EOL, FILE_APPEND);

if (!$update) {
    exit;
}

try {
    $bot = new TelegramBot(TELEGRAM_TOKEN);
    $userRepo = new UserRepository(); // <--- Тут може впасти через підключення до БД
    $userService = new UserService($userRepo);

    $controller = new BotController($bot, $userService);
    $controller->handleUpdate($update);

} catch (\Throwable $e) {
    file_put_contents('bot_errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL, FILE_APPEND);
}