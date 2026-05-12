<?php
namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Services\Interfaces\ConsumableServiceInterface;
use App\Utils\TelegramBot;

class ListMaterialsCommand implements CommandInterface {
    public function __construct(
        private TelegramBot $bot,
        private ConsumableServiceInterface $consumableService
    ) {}

    public function execute(int $chatId, array $data = []): void {
        $materials = $this->consumableService->getConsumablesList();

        if (empty($materials)) {
            $this->bot->sendMessage($chatId, "На складі поки порожньо. Додайте матеріали через БД.");
            return;
        }

        $response = "📦 **Наявні матеріали:**\n\n";
        foreach ($materials as $item) {
            $response .= "🔹 {$item->getName()} ({$item->getBrand()})\n";
            $response .= "   Залишок: {$item->getCurrentAmount()}{$item->getUnit()}\n";
            $response .= "   Колір: {$item->getColor()}\n\n";
        }

        $this->bot->sendMessage($chatId, $response);
    }
}