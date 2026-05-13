<?php

namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class AddConsumableCommand implements CommandInterface
{
    private array $allowedTypes = ['PLA', 'PETG', 'TPU', 'PLA+', 'PLA High-speed', 'PETG High-speed', 'ABS'];
    private array $categories = ['Філамент' => 'filament', 'Смола' => 'resin'];

    public function __construct(
        private TelegramBot $bot,
        private StateServiceInterface $stateService,
        private ConsumableServiceInterface $consumableService
    ) {}

    public function execute(int $chatId, array $data): void
    {
        $stateData = $this->stateService->getCurrentStateFull($chatId);
        $input = $data['message']['text'] ?? '';

        if (!$stateData) {
            $this->stateService->setNextState($chatId, 'ADD_WAITING_CATEGORY');
            $this->bot->sendReplyKeyboard($chatId, "Оберіть категорію матеріалу:", array_keys($this->categories));
            return;
        }

        $rawContext = $stateData->getContextData();
        $context = $rawContext ? json_decode($rawContext, true) : [];

        switch ($stateData->getState()) {
            case 'ADD_WAITING_CATEGORY':
                if (!isset($this->categories[$input])) {
                    $this->bot->sendMessage($chatId, "⚠️ Будь ласка, оберіть категорію з кнопок.");
                    return;
                }

                $category = $this->categories[$input];
                $context['category'] = $category;

                if ($category === 'filament') {
                    $this->stateService->setNextState($chatId, 'ADD_WAITING_TYPE', $context);
                    $this->bot->sendReplyKeyboard($chatId, "Оберіть тип пластику:", $this->allowedTypes);
                } else {
                    $context['type'] = 'resin';
                    $this->stateService->setNextState($chatId, 'ADD_WAITING_NAME', $context);
                    $this->bot->sendMessage($chatId, "📝 Введіть назву смоли (наприклад, Anycubic Grey). Або надішліть '-', щоб пропустити:", ['remove_keyboard' => true]);
                }
                break;

            case 'ADD_WAITING_TYPE':
                if (!in_array($input, $this->allowedTypes)) {
                    $this->bot->sendMessage($chatId, "⚠️ Такого типу немає в списку. Оберіть варіант на клавіатурі.");
                    return;
                }

                $context['type'] = $input;
                $this->stateService->setNextState($chatId, 'ADD_WAITING_NAME', $context);
                $this->bot->sendMessage($chatId, "📝 Введіть власну назву або специфікацію (наприклад, Black HQ). Або надішліть '-', щоб пропустити:", ['remove_keyboard' => true]);
                break;

            case 'ADD_WAITING_NAME':
                $context['name'] = ($input === '-') ? null : $input;

                $this->stateService->setNextState($chatId, 'ADD_WAITING_COLOR', $context);
                $this->bot->sendMessage($chatId, "🎨 Введіть колір матеріалу (наприклад, Чорний). Або надішліть '-', щоб пропустити:");
                break;

            case 'ADD_WAITING_COLOR':
                $context['color'] = ($input === '-') ? null : $input;

                $this->stateService->setNextState($chatId, 'ADD_WAITING_BRAND', $context);
                $this->bot->sendMessage($chatId, "🏷 Введіть бренд/виробника (наприклад, Devil Design). Або надішліть '-', щоб пропустити:");
                break;

            case 'ADD_WAITING_BRAND':
                $context['brand'] = ($input === '-') ? null : $input;

                $this->stateService->setNextState($chatId, 'ADD_WAITING_WEIGHT', $context);
                $this->bot->sendMessage($chatId, "⚖️ Введіть початкову вагу котушки/пляшки в грамах:");
                break;

            case 'ADD_WAITING_WEIGHT':
                $weight = filter_var($input, FILTER_VALIDATE_FLOAT);

                if ($weight === false || $weight <= 0) {
                    $this->bot->sendMessage($chatId, "❌ Будь ласка, введіть коректне число більше за 0.");
                    return;
                }

                try {
                    $this->consumableService->createConsumable([
                        'name' => $context['name'],
                        'category' => $context['category'],
                        'type' => $context['type'],
                        'color' => $context['color'],
                        'brand' => $context['brand'],
                        'initial_amount' => $weight
                    ]);

                    $this->stateService->clearState($chatId);

                    $parts = array_filter([
                        $context['type'] ?? null,
                        $context['brand'] ?? null,
                        $context['name'] ?? null,
                        $context['color'] ?? null
                    ]);

                    $displayName = implode(' ', $parts);

                    $this->bot->sendMessage($chatId, "✅ Матеріал **{$displayName}** успішно додано на склад!");
                    $this->bot->sendMainMenu($chatId);

                } catch (\Exception $e) {
                    $this->bot->sendMessage($chatId, "❌ Помилка: " . $e->getMessage());
                    $this->bot->sendMainMenu($chatId);
                }
                break;
        }
    }
}