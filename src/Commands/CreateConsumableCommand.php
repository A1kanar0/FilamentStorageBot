<?php

namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\StateServiceInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class CreateConsumableCommand implements CommandInterface
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
            $this->initiateCreationProcess($chatId);
            return;
        }

        $rawContext = $stateData->getContextData();
        $context = $rawContext ? json_decode($rawContext, true) : [];
        $currentState = $stateData->getState();

        // Refactored: Dispatching logic to dedicated methods instead of a huge switch statement
        $this->handleState($currentState, $chatId, $input, $context);
    }

    private function initiateCreationProcess(int $chatId): void
    {
        $this->stateService->setNextState($chatId, 'ADD_WAITING_CATEGORY');
        $this->bot->sendReplyKeyboard($chatId, "Оберіть категорію матеріалу:", array_keys($this->categories));
    }

    private function handleState(string $state, int $chatId, string $input, array $context): void
    {
        match ($state) {
            'ADD_WAITING_CATEGORY' => $this->processCategorySelection($chatId, $input, $context),
            'ADD_WAITING_TYPE'     => $this->processTypeSelection($chatId, $input, $context),
            'ADD_WAITING_NAME'     => $this->processNameInput($chatId, $input, $context),
            'ADD_WAITING_COLOR'    => $this->processColorInput($chatId, $input, $context),
            'ADD_WAITING_BRAND'    => $this->processBrandInput($chatId, $input, $context),
            'ADD_WAITING_WEIGHT'   => $this->processWeightInputAndSave($chatId, $input, $context),
            default                => $this->bot->sendMessage($chatId, "⚠️ Невідомий стан. Почніть спочатку.")
        };
    }

    private function processCategorySelection(int $chatId, string $input, array $context): void
    {
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
            $this->bot->sendMessage($chatId, "📝 Введіть назву смоли (наприклад, Standard HD). Або надішліть '-', щоб пропустити:", ['remove_keyboard' => true]);
        }
    }

    private function processTypeSelection(int $chatId, string $input, array $context): void
    {
        if (!in_array($input, $this->allowedTypes)) {
            $this->bot->sendMessage($chatId, "⚠️ Такого типу немає в списку. Оберіть варіант на клавіатурі.");
            return;
        }

        $context['type'] = $input;
        $this->stateService->setNextState($chatId, 'ADD_WAITING_NAME', $context);
        $this->bot->sendMessage($chatId, "📝 Введіть власну назву або специфікацію (наприклад, Matte). Або надішліть '-', щоб пропустити:", ['remove_keyboard' => true]);
    }

    private function processNameInput(int $chatId, string $input, array $context): void
    {
        $context['name'] = ($input === '-') ? null : $input;
        $this->stateService->setNextState($chatId, 'ADD_WAITING_COLOR', $context);
        $this->bot->sendMessage($chatId, "🎨 Введіть колір матеріалу (наприклад, Чорний). Або надішліть '-', щоб пропустити:");
    }

    private function processColorInput(int $chatId, string $input, array $context): void
    {
        $context['color'] = ($input === '-') ? null : $input;
        $this->stateService->setNextState($chatId, 'ADD_WAITING_BRAND', $context);
        $this->bot->sendMessage($chatId, "🏷 Введіть бренд/виробника (наприклад, Devil Design). Або надішліть '-', щоб пропустити:");
    }

    private function processBrandInput(int $chatId, string $input, array $context): void
    {
        $context['brand'] = ($input === '-') ? null : $input;
        $this->stateService->setNextState($chatId, 'ADD_WAITING_WEIGHT', $context);
        $this->bot->sendMessage($chatId, "⚖️ Введіть початкову вагу котушки/пляшки в грамах:");
    }

    private function processWeightInputAndSave(int $chatId, string $input, array $context): void
    {
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

            $this->bot->sendMessage($chatId, "✅ Новий матеріал **{$displayName}** успішно внесено до реєстру!");
            $this->bot->sendMainMenu($chatId);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'вже зареєстрований') !== false) {
                $this->bot->sendMessage($chatId, "❌ " . $errorMessage);
                $this->stateService->clearState($chatId);
                $this->bot->sendMainMenu($chatId);
            } else {
                $this->bot->sendMessage($chatId, "❌ Помилка: " . $errorMessage . "\n\n⚖️ Спробуйте ввести вагу ще раз:");
            }
        }
    }
}
