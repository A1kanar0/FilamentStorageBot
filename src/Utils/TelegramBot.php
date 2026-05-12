<?php
namespace App\Utils;

class TelegramBot {
    private string $apiUrl;

    public function __construct(string $token) {
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void {
        $data = ['chat_id' => $chatId, 'text' => $text];
        if ($replyMarkup) $data['reply_markup'] = json_encode($replyMarkup);

        file_get_contents($this->apiUrl . "sendMessage?" . http_build_query($data));
    }
}