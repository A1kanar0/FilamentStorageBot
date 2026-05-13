<?php
namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class AddStockCommand implements CommandInterface
{
    public function __construct(
        private TelegramBot $bot,
        private StateServiceInterface $stateService,
        private ConsumableServiceInterface $consumableService
    ) {}

    public function execute(int $chatId, array $data): void
    {
        $stateData = $this->stateService->getCurrentStateFull($chatId);

        if (!$stateData) {
            $materials = $this->consumableService->getConsumablesList();
            if (empty($materials)) {
                $this->bot->sendMessage($chatId, "❌ На складі порожньо. Спочатку створіть матеріал.");
                return;
            }

            $buttons = [];
            foreach ($materials as $item) {
                $label = $item->getFormattedName() . " (" . $item->getCurrentAmount() . "г)";
                $buttons[] = [['text' => $label, 'callback_data' => 'add_stock_id_' . $item->getId()]];
            }

            $this->stateService->setNextState($chatId, 'ADD_STOCK_WAITING_MATERIAL');
            $this->bot->sendMessage($chatId, "📦 Який матеріал поповнюємо?", [
                'inline_keyboard' => $buttons
            ]);
            return;
        }

        $context = json_decode($stateData->getContextData(), true) ?: [];

        if (isset($data['callback_query'])) {
            $callbackData = $data['callback_query']['data'];
            if (strpos($callbackData, 'add_stock_id_') === 0) {
                $id = (int) str_replace('add_stock_id_', '', $callbackData);
                $context['consumable_id'] = $id;

                $this->stateService->setNextState($chatId, 'ADD_STOCK_WAITING_AMOUNT', $context);
                $this->bot->sendMessage($chatId, "⚖️ Скільки грамів приїхало?");
            }
            return;
        }

        if ($stateData->getState() === 'ADD_STOCK_WAITING_AMOUNT') {
            $amount = filter_var($data['message']['text'] ?? '', FILTER_VALIDATE_FLOAT);

            if ($amount === false || $amount <= 0) {
                $this->bot->sendMessage($chatId, "❌ Введіть число більше нуля.");
                return;
            }

            try {
                $this->consumableService->addStock($context['consumable_id'], $amount);
                $this->stateService->clearState($chatId);
                $this->bot->sendMessage($chatId, "✅ Залишок успішно збільшено на **{$amount}г**!");
                $this->bot->sendMainMenu($chatId);
            } catch (\Exception $e) {
                $this->bot->sendMessage($chatId, "❌ Помилка: " . $e->getMessage());
                $this->stateService->clearState($chatId);
            }
        }
    }
}