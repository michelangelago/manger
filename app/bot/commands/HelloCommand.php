<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use SergiX44\Nutgram\Nutgram;

final class HelloCommand
{
    public function __invoke(Nutgram $bot, string $name): void
    {
        $bot->sendMessage("Hello, {$name}!");
    }
}
