<?php
namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Utils\TelegramBot;

class StartCommand implements CommandInterface {
    public function __construct(
        private TelegramBot $bot,
        private UserServiceInterface $userService
    ) {}

    public function execute(int $chatId, array $data): void {
        $firstName = $data['from']['first_name'] ?? 'Колего';

        $this->userService->authorize($chatId, $firstName);

        $menu = [
            'inline_keyboard' => [
                [['text' => '📋 Склад', 'callback_data' => 'list_materials']],
                [['text' => '➕ Додати матеріал', 'callback_data' => 'add_material']],
                [['text' => '➖ Списати', 'callback_data' => 'start_deduct']]
            ]
        ];

        $this->bot->sendMessage($chatId, "Привіт, {$firstName}! Оберіть дію:", $menu);
    }
}