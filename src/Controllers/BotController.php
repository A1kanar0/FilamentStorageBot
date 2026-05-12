<?php

namespace App\Controllers;

use App\Utils\TelegramBot;
use App\Services\Interfaces\UserServiceInterface;
use App\Commands\StartCommand;
use App\Commands\ListMaterialsCommand;

class BotController
{
    private array $commandMap;

    public function __construct(
        private TelegramBot $bot,
        private UserServiceInterface $userService
    ) {
        $this->commandMap = [
            '/start'         => StartCommand::class,
            'list_materials' => ListMaterialsCommand::class,
        ];
    }

    public function handleUpdate(array $update): void
    {
        $chatId = null;
        $data = null;

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $data = $update['message']['text'] ?? '';
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $data = $update['callback_query']['data'];
        }

        if ($chatId && isset($this->commandMap[$data])) {
            $this->executeCommand($this->commandMap[$data], $chatId, $update);
        }
    }

    private function executeCommand(string $commandClass, int $chatId, array $update): void
    {
        $command = match ($commandClass) {
            StartCommand::class => new StartCommand($this->bot, $this->userService),
            ListMaterialsCommand::class => new ListMaterialsCommand(
                $this->bot,
                new \App\Services\ConsumableService(new \App\Repositories\ConsumableRepository())
            ),
            default => null
        };

        if ($command) {
            $command->execute($chatId, $update);
        }
    }
}