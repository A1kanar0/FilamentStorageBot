<?php

namespace App\Commands;

use App\Commands\Interfaces\CommandInterface;
use App\Repositories\Interfaces\UsageLogRepositoryInterface;
use App\Utils\TelegramBot;

class HistoryCommand implements CommandInterface
{
    public function __construct(
        private TelegramBot $bot,
        private UsageLogRepositoryInterface $usageLogRepository
    ) {}

    public function execute(int $chatId, array $data): void
    {
        $logs = $this->usageLogRepository->getLatestLogs(10);

        if (empty($logs)) {
            $this->bot->sendMessage($chatId, "📭 Журнал списань поки порожній. Спишіть щось, щоб побачити історію.");
            $this->bot->sendMainMenu($chatId);
            return;
        }

        $response = "📜 **Останні 10 списань:**\n\n";

        foreach ($logs as $log) {
            $date = date('d.m H:i', strtotime($log['created_at']));
            $userName = $log['user_name'] ?? 'Анонім';

            $parts = array_filter([
                $log['type'] ?? '',
                $log['brand'] ?? '',
                $log['consumable_name'] ?? '',
                $log['color'] ?? ''
            ]);
            $materialName = implode(' ', $parts);

            $response .= "📅 {$date} — **-{$log['spent_amount']}г**\n";
            $response .= "📦 {$materialName}\n";
            $response .= "👤 {$userName}\n";
            $response .= "----------------------\n";
        }

        $this->bot->sendMessage($chatId, $response);
        $this->bot->sendMainMenu($chatId);
    }
}