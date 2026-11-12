<?php

declare(strict_types=1);

use App\Bot\Commands\HelloCommand;
use App\Bot\Commands\HelpCommand;
use App\Bot\Commands\FoodGroupCommand;
use App\Bot\Commands\StartCommand;
use App\Database\Repositories\UserRepository;
use App\Database\Repositories\FoodGroupRepository;
use SergiX44\Nutgram\Nutgram;

require_once __DIR__ . '/commands/StartCommand.php';
require_once __DIR__ . '/commands/HelloCommand.php';
require_once __DIR__ . '/commands/FoodGroupCommand.php';
require_once __DIR__ . '/commands/HelpCommand.php';

return static function (Nutgram $bot, UserRepository $users, FoodGroupRepository $groups): void {
    $bot->onCommand('start', new StartCommand($users));
    $bot->onCommand('hello {name}', new HelloCommand());
    $bot->onCommand('help', new HelpCommand());
    $foodGroups = new FoodGroupCommand($groups);
    $bot->onCommand('fgroup', $foodGroups);
    $bot->onCommand('fgroup {arguments}', $foodGroups);
};
