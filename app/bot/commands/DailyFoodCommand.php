<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Bot\Support\DailyFoodView;
use App\Database\Repositories\DailyFoodRepository;
use App\Database\Repositories\FoodRepository;
use App\Database\Repositories\UserRepository;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__) . '/support/CommandInput.php';
require_once dirname(__DIR__) . '/support/DailyFoodView.php';
require_once dirname(__DIR__, 2) . '/database/repositories/DailyFoodRepository.php';

final class DailyFoodCommand
{
    public const USAGE = "Consumed foods:\n/dfoods -I\n"
        . "/dfoods -C \"food name\" grams\n/dfoods -R entry_id\n"
        . "/dfoods -U entry_id \"food name\" grams\n/dfoods -D entry_id\n"
        . 'Use positive whole grams. -C logs consumption now; -U keeps the original date and time.';

    private Closure $clock;

    public function __construct(private UserRepository $users, private FoodRepository $foods, private DailyFoodRepository $entries, DateTimeZone $timezone, ?Closure $clock = null)
    {
        $this->clock = static fn (): DateTimeImmutable => ($clock === null ? new DateTimeImmutable('now') : $clock())->setTimezone($timezone);
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = CommandInput::tokens($arguments);
        $operation = CommandInput::operation($tokens[0] ?? '');
        $counts = ['index' => 1, 'create' => 3, 'read' => 2, 'update' => 4, 'delete' => 2];
        if ($tokens === null || $operation === null || !isset($counts[$operation]) || count($tokens) !== $counts[$operation]) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $id = in_array($operation, ['read', 'update', 'delete'], true) ? CommandInput::positiveInteger($tokens[1]) : null;
        $quantity = in_array($operation, ['create', 'update'], true) ? CommandInput::positiveInteger($tokens[count($tokens) - 1]) : null;
        if ((in_array($operation, ['read', 'update', 'delete'], true) && $id === null)
            || (in_array($operation, ['create', 'update'], true) && $quantity === null)) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $sender = $bot->user();
        $user = $sender === null ? null : $this->users->findByTelegramId($sender->id);
        if ($user === null) {
            $bot->sendMessage('Send /start to register first.');
            return;
        }
        $userId = (int) $user['id'];
        if ($operation === 'index') {
            $entries = $this->entries->indexForUser($userId);
            $listing = $entries === [] ? 'No consumed foods registered.' : implode("\n\n", array_map([DailyFoodView::class, 'describe'], $entries));
            $bot->sendMessage($listing . "\n\n" . self::USAGE);
            return;
        }
        if ($id !== null) {
            $entry = $this->entries->readForUser($id, $userId);
            if ($entry === null) {
                $bot->sendMessage('Food entry not found.');
                return;
            }
            if ($operation === 'read') {
                $bot->sendMessage(DailyFoodView::describe($entry));
                return;
            }
            if ($operation === 'delete') {
                $this->entries->deleteForUser($id, $userId);
                $bot->sendMessage("Food entry deleted: {$id}");
                return;
            }
        }
        $foodName = $tokens[$operation === 'create' ? 1 : 2];
        $food = $this->foods->findByName($foodName);
        if ($food === null) {
            $bot->sendMessage("Food not found: {$foodName}\nView foods with /food -I.");
            return;
        }
        if ($operation === 'create') {
            $id = $this->entries->registerConsumption($userId, (int) $food['id'], $quantity, ($this->clock)());
            $message = 'Food entry created!';
        } else {
            $this->entries->updateForUser($id, $userId, (int) $food['id'], $quantity);
            $message = 'Food entry updated!';
        }
        $bot->sendMessage($message . "\n" . DailyFoodView::describe($this->entries->readForUser($id, $userId)));
    }
}
