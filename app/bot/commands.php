<?php

declare(strict_types=1);

use App\Bot\Commands\HelpCommand;
use App\Bot\Commands\FoodGroupCommand;
use App\Bot\Commands\FoodCommand;
use App\Bot\Commands\StartCommand;
use App\Database\Repositories\UserRepository;
use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use SergiX44\Nutgram\Nutgram;

require_once __DIR__ . '/commands/StartCommand.php';
require_once __DIR__ . '/commands/FoodGroupCommand.php';
require_once __DIR__ . '/commands/HelpCommand.php';
require_once __DIR__ . '/commands/FoodCommand.php';

return static function (Nutgram $bot, UserRepository $users, FoodGroupRepository $groups, FoodRepository $foods): void {
    $bot->onCommand('start', new StartCommand($users));
    $bot->onCommand('help', new HelpCommand());
    $foodGroups = new FoodGroupCommand($groups);
    $bot->onCommand('fgroup', $foodGroups);
    $bot->onCommand('fgroup {arguments}', $foodGroups);
    $food = new FoodCommand($foods, $groups);
    $bot->onCommand('food', $food);
    $bot->onCommand('food {arguments}', $food);
};
