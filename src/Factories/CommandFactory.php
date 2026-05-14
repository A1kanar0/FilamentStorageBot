<?php
namespace App\Factories;

use App\Commands\Interfaces\CommandInterface;
use App\Commands\StartCommand;
use App\Commands\ListMaterialsCommand;
use App\Commands\CreateConsumableCommand;
use App\Commands\DeductMaterialCommand;
use App\Commands\AddStockCommand;
use App\Commands\HistoryCommand;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Repositories\Interfaces\UsageLogRepositoryInterface;
use App\Utils\TelegramBot;

class CommandFactory
{
    public function __construct(
        private TelegramBot $bot,
        private StateServiceInterface $stateService,
        private UserServiceInterface $userService,
        private ConsumableServiceInterface $consumableService,
        private UsageLogRepositoryInterface $usageLogRepo
    ) {}

    public function create(string $commandClass): ?CommandInterface
    {
        return match ($commandClass) {
            StartCommand::class => new StartCommand($this->bot, $this->stateService, $this->userService),
            ListMaterialsCommand::class => new ListMaterialsCommand($this->bot, $this->consumableService),
            CreateConsumableCommand::class => new CreateConsumableCommand($this->bot, $this->stateService, $this->consumableService),
            DeductMaterialCommand::class => new DeductMaterialCommand($this->bot, $this->stateService, $this->consumableService),
            AddStockCommand::class => new AddStockCommand($this->bot, $this->stateService, $this->consumableService),
            HistoryCommand::class => new HistoryCommand($this->bot, $this->usageLogRepo),
            default => null
        };
    }
}