<?php

namespace App;

use App\Utils\TelegramBot;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Repositories\UserStateRepository;
use App\Services\StateService;
use App\Repositories\ConsumableRepository;
use App\Repositories\UsageLogRepository;
use App\Services\ConsumableService;
use App\Factories\CommandFactory;
use App\Controllers\BotController;

class AppContainer
{
    private array $instances = [];

    public function __construct()
    {
        $this->buildContainer();
    }

    private function buildContainer(): void
    {
        // Інфраструктура та базові репозиторії
        $this->instances[TelegramBot::class] = new TelegramBot(TELEGRAM_TOKEN);
        $this->instances[UserRepository::class] = new UserRepository();
        $this->instances[UserStateRepository::class] = new UserStateRepository();
        $this->instances[ConsumableRepository::class] = new ConsumableRepository();
        $this->instances[UsageLogRepository::class] = new UsageLogRepository();

        // Сервіси
        $this->instances[UserService::class] = new UserService($this->instances[UserRepository::class]);
        $this->instances[StateService::class] = new StateService($this->instances[UserStateRepository::class]);
        $this->instances[ConsumableService::class] = new ConsumableService(
            $this->instances[ConsumableRepository::class],
            $this->instances[UsageLogRepository::class]
        );

        // Фабрика команд
        $this->instances[CommandFactory::class] = new CommandFactory(
            $this->instances[TelegramBot::class],
            $this->instances[StateService::class],
            $this->instances[UserService::class],
            $this->instances[ConsumableService::class],
            $this->instances[UsageLogRepository::class]
        );

        // Головний контролер
        $this->instances[BotController::class] = new BotController(
            $this->instances[TelegramBot::class],
            $this->instances[UserService::class],
            $this->instances[StateService::class],
            $this->instances[CommandFactory::class]
        );
    }

    public function getBotController(): BotController
    {
        return $this->instances[BotController::class];
    }
}
