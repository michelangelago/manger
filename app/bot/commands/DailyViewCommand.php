<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Bot\Support\DailySummaryView;
use App\Database\Repositories\DailyFoodRepository;
use App\Database\Repositories\UserRepository;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__) . '/support/CommandInput.php';
require_once dirname(__DIR__) . '/support/DailySummaryView.php';
require_once dirname(__DIR__, 2) . '/database/repositories/DailyFoodRepository.php';

final class DailyViewCommand
{
    public const USAGE = "Calorie summary:\n/dview [-D|-W|-M|-Y] [YYYY-MM-DD]\n"
        . 'Defaults to today. -D Day, -W Week (Mon–Sun), -M Month, -Y Year. Uses the configured timezone.';

    private Closure $clock;

    public function __construct(private UserRepository $users, private DailyFoodRepository $entries, private DateTimeZone $timezone, ?Closure $clock = null)
    {
        $this->clock = static fn (): DateTimeImmutable => ($clock === null ? new DateTimeImmutable('now') : $clock())->setTimezone($timezone);
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = trim($arguments) === '' ? [] : CommandInput::tokens($arguments);
        if ($tokens === null || count($tokens) > 2) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $flag = null;
        $date = null;
        foreach ($tokens as $token) {
            if (in_array(strtoupper($token), ['-D', '-W', '-M', '-Y'], true) && $flag === null) {
                $flag = strtoupper($token);
            } elseif (CommandInput::validDate($token) && $date === null) {
                $date = $token;
            } else {
                $bot->sendMessage(self::USAGE);
                return;
            }
        }
        $sender = $bot->user();
        $user = $sender === null ? null : $this->users->findByTelegramId($sender->id);
        if ($user === null) {
            $bot->sendMessage('Send /start to register first.');
            return;
        }
        $day = $date === null ? ($this->clock)()->setTime(0, 0) : new DateTimeImmutable($date, $this->timezone);
        [$start, $label] = match ($flag ?? '-D') {
            '-W' => [$day->modify('-' . ((int) $day->format('N') - 1) . ' days'), 'Week'],
            '-M' => [$day->modify('first day of this month'), 'Month'],
            '-Y' => [$day->setDate((int) $day->format('Y'), 1, 1), 'Year'],
            default => [$day, 'Day'],
        };
        $end = $start->modify(match ($flag ?? '-D') {
            '-W' => '+1 week', '-M' => '+1 month', '-Y' => '+1 year', default => '+1 day',
        });
        $range = $start->format('Y-m-d');
        if (($flag ?? '-D') !== '-D') {
            $range .= ' to ' . $end->modify('-1 day')->format('Y-m-d');
        }
        $foods = $this->entries->summarizeForUser((int) $user['id'], $start->format('Y-m-d'), $end->format('Y-m-d'));
        $bot->sendMessage(DailySummaryView::render($label . ' · ' . $range, $foods));
    }
}
