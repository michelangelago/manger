<?php

declare(strict_types=1);

use App\Bot\Commands\HelpCommand;
use App\Bot\Commands\DailyCommand;
use App\Bot\Commands\DailyFoodCommand;
use App\Bot\Commands\DailyViewCommand;
use App\Bot\Commands\GroupViewCommand;
use App\Bot\Commands\GoalCommand;
use App\Bot\Commands\EatCommand;
use App\Database\Repositories\GoalRepository;
use App\Database\Repositories\DailyRepository;
use App\Database\Repositories\DailyFoodRepository;
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
require_once __DIR__ . '/commands/DailyCommand.php';
require_once __DIR__ . '/commands/DailyFoodCommand.php';
require_once __DIR__ . '/commands/DailyViewCommand.php';
require_once __DIR__ . '/commands/GroupViewCommand.php';
require_once __DIR__ . '/commands/GoalCommand.php';
require_once __DIR__ . '/commands/EatCommand.php';

return static function (Nutgram $bot, UserRepository $users, FoodGroupRepository $groups, FoodRepository $foods, DailyRepository $daily, DailyFoodRepository $entries, GoalRepository $goals, DateTimeZone $timezone = new DateTimeZone('America/Recife'), ?Closure $clock = null): void {
    $bot->onCommand('start', new StartCommand($users));
    $bot->onCommand('help', new HelpCommand());
    $foodGroups = new FoodGroupCommand($groups);
    $bot->onCommand('fgroup', $foodGroups);
    $bot->onCommand('fgroup {arguments}', $foodGroups);
    $food = new FoodCommand($foods, $groups);
    $bot->onCommand('food', $food);
    $bot->onCommand('food {arguments}', $food);
    $dailyCommand = new DailyCommand($users, $daily, $entries, $timezone, $clock);
    $bot->onCommand('daily', $dailyCommand);
    $bot->onCommand('daily {arguments}', $dailyCommand);
    $dailyFoodCommand = new DailyFoodCommand($users, $foods, $entries, $timezone, $clock);
    $bot->onCommand('dfoods', $dailyFoodCommand);
    $bot->onCommand('dfoods {arguments}', $dailyFoodCommand);
    $eat = new EatCommand($dailyFoodCommand);
    $bot->onCommand('eat', $eat);
    $bot->onCommand('eat {arguments}', $eat);
    $dailyView = new DailyViewCommand($users, $entries, $goals, $timezone, $clock);
    $bot->onCommand('dview', $dailyView);
    $bot->onCommand('dview {arguments}', $dailyView);
    $groupView = new GroupViewCommand($groups, $foods);
    $bot->onCommand('gview', $groupView);
    $bot->onCommand('gview {arguments}', $groupView);
    $goal = new GoalCommand($users, $goals);
    $bot->onCommand('goal', $goal);
    $bot->onCommand('goal {arguments}', $goal);
};
