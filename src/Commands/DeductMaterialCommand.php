<?php

namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class DeductMaterialCommand implements CommandInterface
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
                $this->bot->sendMessage($chatId, "❌ На складі немає матеріалів для списання.");
                $this->bot->sendMainMenu($chatId);
                return;
            }

            $buttons = [];
            foreach ($materials as $item) {
                $name = $item->getFormattedName() . " ({$item->getCurrentAmount()}г)";
                $buttons[] = [['text' => $name, 'callback_data' => 'deduct_id_' . $item->getId()]];
            }

            $this->stateService->setNextState($chatId, 'DEDUCT_WAITING_FOR_MATERIAL');
            $this->bot->sendMessage($chatId, "🎯 Оберіть матеріал для списання:", [
                'inline_keyboard' => $buttons
            ]);
            return;
        }

        $context = json_decode($stateData->getContextData(), true) ?: [];

        if ($stateData->getState() === 'DEDUCT_WAITING_FOR_MATERIAL') {
            $callbackData = $data['callback_query']['data'] ?? '';

            if (strpos($callbackData, 'deduct_id_') === 0) {
                $id = (int) str_replace('deduct_id_', '', $callbackData);
                $context['consumable_id'] = $id;

                $this->stateService->setNextState($chatId, 'DEDUCT_WAITING_FOR_AMOUNT', $context);

                $this->bot->sendMessage($chatId, "⚖️ Введіть вагу в грамах, яку потрібно списати (тільки цифри):");
            }
            return;
        }

        if ($stateData->getState() === 'DEDUCT_WAITING_FOR_AMOUNT') {
            $amount = filter_var($data['message']['text'] ?? '', FILTER_VALIDATE_FLOAT);

            if ($amount === false || $amount <= 0) {
                $this->bot->sendMessage($chatId, "❌ Будь ласка, введіть коректне число (наприклад, 45.5).");
                return;
            }

            try {
                $item = $this->consumableService->getConsumable($context['consumable_id']);
                $remainingBefore = $item->getCurrentAmount();

                $this->consumableService->deductMaterial($context['consumable_id'], $amount);

                $this->stateService->clearState($chatId);

                if ($remainingBefore - $amount <= 0) {
                    $message = "✅ Успішно списано останні **{$amount}г**. Матеріал вичерпано та видалено зі складу!";
                } else {
                    $message = "✅ Успішно списано **{$amount}г**. Залишок оновлено!";
                }

                $this->bot->sendMessage($chatId, $message);
                $this->bot->sendMainMenu($chatId);

            } catch (\Exception $e) {
                $this->bot->sendMessage($chatId, "❌ Помилка: " . $e->getMessage());
                $this->stateService->clearState($chatId);
                $this->bot->sendMainMenu($chatId);
            }
        }
    }
}