<?php

declare(strict_types=1);

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommand;

return static function (Nutgram $bot): void {
    $bot->setMyCommands([
        BotCommand::make('start', 'Register or view your profile'),
        BotCommand::make('help', 'Show commands and operation flags'),
        BotCommand::make('fgroup', 'Food groups: -I list, -C create, -R read, -U update, -D delete'),
        BotCommand::make('food', 'Foods: -I list, -C create, -R read, -U update, -D delete'),
        BotCommand::make('daily', 'Daily logs: -I list, -R [YYYY-MM-DD] read; defaults to today'),
        BotCommand::make('dfoods', 'Consumed foods: -I list, -C create, -R read, -U update, -D delete'),
        BotCommand::make('dview', 'Calorie summary: -D day, -W week, -M month, -Y year; defaults to today'),
        BotCommand::make('gview', 'View foods by group; optionally add a group name'),
        BotCommand::make('goal', 'Daily calorie goal: -C calories, -R read, -U calories, -D delete'),
        BotCommand::make('eat', 'Quick log: /eat "food name" grams'),
    ]);
};
