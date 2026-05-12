<?php
namespace App\Controllers;

use App\Commands\StartCommand;
use App\Services\Interfaces\UserServiceInterface;
use App\Utils\TelegramBot;

class BotController {
    public function __construct(
        private TelegramBot $bot,
        private UserServiceInterface $userService
    ) {}

    public function handleUpdate(array $update): void {
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            if ($text === '/start') {
                $command = new StartCommand($this->bot, $this->userService);
                $command->execute($chatId, $update['message']);
            }
        }
    }
}