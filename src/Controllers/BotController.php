<?php

namespace App\Controllers;

use App\Commands\CreateConsumableCommand;
use App\Utils\TelegramBot;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Commands\StartCommand;
use App\Commands\ListMaterialsCommand;
use App\Commands\DeductMaterialCommand;

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
            '📋 Склад'           => ListMaterialsCommand::class,
            '➕ Створити матеріал' => CreateConsumableCommand::class,
            '➖ Списати'         => DeductMaterialCommand::class,
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
                $this->executeCommand(CreateConsumableCommand::class, $chatId, $update);
                return;
            }
            if (strpos($stateData->getState(), 'DEDUCT_') === 0) {
                $this->executeCommand(DeductMaterialCommand::class, $chatId, $update);
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
            StartCommand::class => new StartCommand($this->bot, $this->stateService, $this->userService),

            ListMaterialsCommand::class => new ListMaterialsCommand(
                $this->bot,
                new \App\Services\ConsumableService(new \App\Repositories\ConsumableRepository())
            ),

            CreateConsumableCommand::class => new CreateConsumableCommand(
                $this->bot,
                $this->stateService,
                new \App\Services\ConsumableService(new \App\Repositories\ConsumableRepository())
            ),
            DeductMaterialCommand::class => new DeductMaterialCommand(
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