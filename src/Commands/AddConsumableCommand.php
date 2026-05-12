<?php
namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class AddConsumableCommand implements CommandInterface {
    public function __construct(
        private TelegramBot $bot,
        private StateServiceInterface $stateService,
        private ConsumableServiceInterface $consumableService
    ) {}

    public function execute(int $chatId, array $data): void {
        $stateData = $this->stateService->getFullState($chatId);
        $input = $data['message']['text'] ?? '';

        if (!$stateData) {
            $this->stateService->setNextState($chatId, 'ADD_WAITING_NAME');
            $this->bot->sendMessage($chatId, "📝 Введіть назву матеріалу:");
            return;
        }

        switch ($stateData->getState()) {
            case 'ADD_WAITING_NAME':
                $this->stateService->setNextState($chatId, 'ADD_WAITING_WEIGHT', ['name' => $input]);
                $this->bot->sendMessage($chatId, "⚖️ Введіть початкову вагу (г):");
                break;

            case 'ADD_WAITING_WEIGHT':
                $context = json_decode($stateData->getContextData(), true);
                $name = $context['name'];
                $weight = (float)$input;

                $this->consumableService->createConsumable([
                    'name' => $name,
                    'initial_amount' => $weight,
                    'current_amount' => $weight
                ]);

                $this->stateService->clearState($chatId);
                $this->bot->sendMessage($chatId, "✅ Матеріал **$name** ($weight г) додано на склад!");
                break;
        }
    }
}