<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use SergiX44\Nutgram\Nutgram;

final class HelpCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessage(
            "Available commands:\n\n"
            . "/start — Register or view your saved information.\n"
            . "/hello Maria — Get a personalized greeting.\n"
            . "/help — Show available commands.\n\n"
            . FoodGroupCommand::USAGE
            . "\n\nQuote names containing spaces."
        );
    }
}
