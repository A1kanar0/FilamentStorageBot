<?php
namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Utils\TelegramBot;

class StartCommand implements CommandInterface {
    public function __construct(
        private TelegramBot $bot,
        private StateServiceInterface $stateService,
        private UserServiceInterface $userService
    ) {}

    public function execute(int $chatId, array $data): void
    {
        $userName = $data['message']['from']['first_name'] ?? 'User';
        $this->userService->authorize($chatId, $userName);

        $this->stateService->clearState($chatId);

        $this->bot->sendMainMenu($chatId, "Оберіть дію:");
    }
}