<?php

namespace App\Controllers;

use App\Commands\AddConsumableCommand;
use App\Utils\TelegramBot;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Interfaces\StateServiceInterface; // Додай імпорт
use App\Commands\StartCommand;
use App\Commands\ListMaterialsCommand;

class BotController
{
    private array $commandMap;

    public function __construct(
        private TelegramBot $bot,
        private UserServiceInterface $userService,
        private StateServiceInterface $stateService
    ) {
        $this->commandMap = [
            '/start'         => StartCommand::class,
            'list_materials' => ListMaterialsCommand::class,
            'add_material'   => AddConsumableCommand::class,
        ];
    }

    public function handleUpdate(array $update): void
    {
        $chatId = null;
        $input = null;

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $input = $update['message']['text'] ?? '';
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $input = $update['callback_query']['data'];
        }

        if (!$chatId) return;

        $stateData = $this->stateService->getCurrentStateFull($chatId);

        if ($stateData && strpos($input, '/') !== 0) {
            if (strpos($stateData->getState(), 'ADD_') === 0) {
                $this->executeCommand(AddConsumableCommand::class, $chatId, $update);
                return;
            }
        }

        if (isset($this->commandMap[$input])) {
            $this->executeCommand($this->commandMap[$input], $chatId, $update);
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

            AddConsumableCommand::class => new AddConsumableCommand(
                $this->bot,
                $this->stateService,
                new \App\Services\ConsumableService(new \App\Repositories\ConsumableRepository())
            ),

            default => null
        };

        if ($command) {
            $command->execute($chatId, $update);
        }
    }
}