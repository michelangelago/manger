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
            . "/help — Show available commands.\n\n"
            . "Operations: -I Index, -C Create, -R Read, -U Update, -D Delete.\n\n"
            . FoodGroupCommand::USAGE
            . "\n\n" . FoodCommand::USAGE
            . "\n\n" . DailyCommand::USAGE
            . "\n\n" . DailyFoodCommand::USAGE
            . "\n\n" . DailyViewCommand::USAGE
            . "\n\nQuote names containing spaces."
        );
    }
}
