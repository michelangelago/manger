<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use App\Database\Repositories\UserRepository;
use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use App\Database\Repositories\DailyRepository;
use App\Database\Repositories\DailyFoodRepository;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Configuration;

$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/vendor/autoload.php';
require_once $projectRoot . '/app/database/repositories/UserRepository.php';

Dotenv::createImmutable($projectRoot)->safeLoad();

$token = trim($_ENV['TELEGRAM_BOT_TOKEN'] ?? '');

if ($token === '') {
    fwrite(STDERR, "Set TELEGRAM_BOT_TOKEN in .env before starting the bot.\n");
    exit(1);
}

$config = new Configuration(
    clientTimeout: 10,
);

$bot = new Nutgram($token, $config);
$pdo = require $projectRoot . '/app/database/connection.php';
$users = new UserRepository($pdo);

$registerCommands = require __DIR__ . '/commands.php';
$registerCommands(
    $bot, $users, new FoodGroupRepository($pdo), new FoodRepository($pdo),
    new DailyRepository($pdo), new DailyFoodRepository($pdo),
    new DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Recife')
);

return $bot;
