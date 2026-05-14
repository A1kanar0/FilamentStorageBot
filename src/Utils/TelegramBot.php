<?php
namespace App\Utils;
use App\Config\BotCommands;
class TelegramBot {

    private string $apiUrl;

    public function __construct(string $token) {
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }


    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void {
        $data = [
            'chat_id' => $chatId,
            'text'    => $text,
            'parse_mode' => 'Markdown'
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        $this->sendRequest('sendMessage', $data);
    }

    private function sendRequest(string $method, array $data): void {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        file_get_contents($this->apiUrl . $method, false, $context);
    }

    public function sendReplyKeyboard(int $chatId, string $text, array $buttons): void {
        $keyboard = [
            'keyboard' => array_chunk($buttons, 2),
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];
        $this->sendMessage($chatId, $text, $keyboard);
    }

    public function sendMainMenu(int $chatId, string $text = "Оберіть дію:"): void {
        $keyboard = [
            'keyboard' => [
                [['text' => BotCommands::BTN_STOCK]],
                [['text' => BotCommands::BTN_CREATE]],
                [['text' => BotCommands::BTN_ADD_STOCK]],
                [['text' => BotCommands::BTN_DEDUCT]],
                [['text' => BotCommands::BTN_HISTORY]]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        $this->sendMessage($chatId, $text, $keyboard);
    }
}