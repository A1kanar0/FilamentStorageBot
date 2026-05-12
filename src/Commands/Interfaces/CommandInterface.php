<?php
namespace App\Commands\Interfaces;

interface CommandInterface {
    public function execute(int $chatId, array $data): void;
}