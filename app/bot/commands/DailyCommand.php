<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Bot\Support\DailyFoodView;
use App\Database\Repositories\DailyRepository;
use App\Database\Repositories\DailyFoodRepository;
use App\Database\Repositories\UserRepository;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__) . '/support/CommandInput.php';
require_once dirname(__DIR__) . '/support/DailyFoodView.php';
require_once dirname(__DIR__, 2) . '/database/repositories/DailyRepository.php';
require_once dirname(__DIR__, 2) . '/database/repositories/DailyFoodRepository.php';

final class DailyCommand
{
    public const USAGE = "Daily logs:\n/daily -I\n/daily -R [YYYY-MM-DD]\n"
        . "Omit the date to view today in the configured timezone.\n"
        . 'A daily log is created automatically when you log your first consumed food of the day.';

    private Closure $clock;

    public function __construct(private UserRepository $users, private DailyRepository $daily, private DailyFoodRepository $entries, DateTimeZone $timezone, ?Closure $clock = null)
    {
        $this->clock = static fn (): DateTimeImmutable => ($clock === null ? new DateTimeImmutable('now') : $clock())->setTimezone($timezone);
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = CommandInput::tokens($arguments);
        $operation = CommandInput::operation($tokens[0] ?? '');
        if ($tokens === null || !in_array($operation, ['index', 'read'], true)
            || ($operation === 'index' && count($tokens) !== 1)
            || ($operation === 'read' && (count($tokens) > 2 || (isset($tokens[1]) && !CommandInput::validDate($tokens[1]))))) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $sender = $bot->user();
        $user = $sender === null ? null : $this->users->findByTelegramId($sender->id);
        if ($user === null) {
            $bot->sendMessage('Send /start to register first.');
            return;
        }
        if ($operation === 'index') {
            $logs = $this->daily->indexForUser((int) $user['id']);
            $listing = $logs === [] ? 'No daily logs registered.' : "Daily logs:\n" . implode("\n", array_map(
                static fn (array $log): string => $log['date'] ?? "Legacy log: {$log['name']}", $logs
            ));
            $bot->sendMessage($listing . "\n\n" . self::USAGE);
            return;
        }
        $date = $tokens[1] ?? ($this->clock)()->format('Y-m-d');
        $log = $this->daily->findByDate((int) $user['id'], $date);
        if ($log === null) {
            $bot->sendMessage("Daily log not found: {$date}");
            return;
        }
        $entries = $this->entries->indexForUser((int) $user['id'], (int) $log['id']);
        $total = number_format(array_sum(array_map([DailyFoodView::class, 'calories'], $entries)), 2, '.', '');
        $listing = $entries === [] ? 'No food entries.' : implode("\n\n", array_map([DailyFoodView::class, 'describe'], $entries));
        $bot->sendMessage("Daily log: {$log['date']}\nTotal calories: {$total} kcal\n\n" . $listing);
    }
}
