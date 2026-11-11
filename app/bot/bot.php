<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use SergiX44\Nutgram\Nutgram;

$projectRoot = dirname(__DIR__, 2);

require $projectRoot . '/vendor/autoload.php';

Dotenv::createImmutable($projectRoot)->safeLoad();

$token = trim($_ENV['TELEGRAM_BOT_TOKEN'] ?? '');

if ($token === '') {
    fwrite(STDERR, "Set TELEGRAM_BOT_TOKEN in .env before starting the bot.\n");
    exit(1);
}

$bot = new Nutgram($token);

$bot->onCommand('start', function (Nutgram $bot): void {
    $bot->sendMessage('Hello! Send /hello followed by your name, for example /hello Maria.');
});

$bot->onCommand('hello {name}', function (Nutgram $bot, string $name): void {
    $bot->sendMessage("Hello, {$name}!");
});

$bot->run();
