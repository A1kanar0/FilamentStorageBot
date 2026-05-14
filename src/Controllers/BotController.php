<?php

namespace App\Controllers;

use App\Factories\CommandFactory;
use App\Commands\CreateConsumableCommand;
use App\Utils\TelegramBot;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Commands\StartCommand;
use App\Commands\ListMaterialsCommand;
use App\Commands\DeductMaterialCommand;
use App\Commands\AddStockCommand;
use App\Commands\HistoryCommand;

class BotController
{
    private array $commandMap;
    private array $stateMap;
    private CommandFactory $commandFactory;

    public function __construct(
        private TelegramBot $bot,
        private UserServiceInterface $userService,
        private StateServiceInterface $stateService,
        CommandFactory $commandFactory
    ) {
        $this->commandFactory = $commandFactory;

        $this->commandMap = [
            '/start'               => StartCommand::class,
            '📋 Склад'             => ListMaterialsCommand::class,
            '➕ Створити матеріал' => CreateConsumableCommand::class,
            '📦 Поповнити залишок' => AddStockCommand::class,
            '➖ Списати'           => DeductMaterialCommand::class,
            '📜 Історія'           => HistoryCommand::class,
        ];

        $this->stateMap = [
            'ADD_STOCK_' => AddStockCommand::class,
            'DEDUCT_'    => DeductMaterialCommand::class,
            'ADD_'       => CreateConsumableCommand::class,
        ];
    }

    public function handleUpdate(array $update): void
    {
        $chatId = null;
        $input = null;
        $username = 'User';

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $input = $update['message']['text'] ?? '';
            $username = $update['message']['from']['username'] ?? 'User';
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $input = $update['callback_query']['data'];
            $username = $update['callback_query']['from']['username'] ?? 'User';
        }

        if (!$chatId) return;

        $this->userService->authorize($chatId, $username);

        $stateData = $this->stateService->getCurrentStateFull($chatId);
        $globalCommands = array_keys($this->commandMap);

        if ($stateData && in_array($input, $globalCommands)) {
            $this->stateService->clearState($chatId);
            $stateData = null;
        }

        if ($stateData && strpos($input, '/') !== 0) {
            $currentState = $stateData->getState();

            foreach ($this->stateMap as $prefix => $commandClass) {
                if (strpos($currentState, $prefix) === 0) {
                    $this->executeCommand($commandClass, $chatId, $update);
                    return;
                }
            }
        }

        if (isset($this->commandMap[$input])) {
            $this->executeCommand($this->commandMap[$input], $chatId, $update);
        }
    }

    private function executeCommand(string $commandClass, int $chatId, array $update): void
    {
        $command = $this->commandFactory->create($commandClass);

        if ($command) {
            $command->execute($chatId, $update);
        }
    }
}