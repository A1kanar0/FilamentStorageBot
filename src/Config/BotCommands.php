<?php
namespace App\Config;

class BotCommands
{
    public const CMD_START = '/start';
    public const BTN_STOCK = '📋 Склад';
    public const BTN_CREATE = '➕ Створити матеріал';
    public const BTN_ADD_STOCK = '📦 Поповнити залишок';
    public const BTN_DEDUCT = '➖ Списати';
    public const BTN_HISTORY = '📜 Історія';

    public const STATE_ADD_STOCK = 'ADD_STOCK_';
    public const STATE_DEDUCT = 'DEDUCT_';
    public const STATE_ADD_MATERIAL = 'ADD_';
}