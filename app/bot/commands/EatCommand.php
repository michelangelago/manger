<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use SergiX44\Nutgram\Nutgram;

require_once __DIR__ . '/DailyFoodCommand.php';

final class EatCommand
{
    public const USAGE = "Quick food logging:\n/eat \"food name\" grams\n"
        . 'Shortcut for /dfoods -C. Use positive whole grams and an existing food name.';

    public function __construct(private DailyFoodCommand $dailyFoods)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = CommandInput::tokens($arguments);
        if ($tokens === null || count($tokens) !== 2 || CommandInput::positiveInteger($tokens[1]) === null) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        ($this->dailyFoods)($bot, '-C ' . $arguments);
    }
}
