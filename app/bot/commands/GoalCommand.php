<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Database\Repositories\GoalRepository;
use App\Database\Repositories\UserRepository;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__) . '/support/CommandInput.php';
require_once dirname(__DIR__, 2) . '/database/repositories/GoalRepository.php';

final class GoalCommand
{
    public const USAGE = "Daily calorie goal:\n/goal -C calories\n/goal -R\n/goal -U calories\n/goal -D\n"
        . 'One goal per user, in kcal/day. Enter a positive whole number.';

    public function __construct(private UserRepository $users, private GoalRepository $goals)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = CommandInput::tokens($arguments);
        $operation = CommandInput::operation($tokens[0] ?? '');
        $writes = in_array($operation, ['create', 'update'], true);
        if ($tokens === null || !in_array($operation, ['create', 'read', 'update', 'delete'], true)
            || count($tokens) !== ($writes ? 2 : 1)) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $calories = $writes ? CommandInput::positiveInteger($tokens[1]) : null;
        if ($writes && $calories === null) {
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
        if ($operation === 'create') {
            $created = $this->goals->createForUser($userId, $calories);
            $bot->sendMessage($created ? "Daily goal created: {$calories} kcal/day." : 'You already have a goal. Use /goal -U calories to change it.');
            return;
        }
        if ($operation === 'update') {
            $updated = $this->goals->updateForUser($userId, $calories);
            $bot->sendMessage($updated ? "Daily goal updated: {$calories} kcal/day." : 'No calorie goal set. Use /goal -C calories to create one.');
            return;
        }
        if ($operation === 'delete') {
            $deleted = $this->goals->deleteForUser($userId);
            $bot->sendMessage($deleted ? 'Daily calorie goal deleted.' : 'No calorie goal set.');
            return;
        }
        $goal = $this->goals->findByUserId($userId);
        $bot->sendMessage($goal === null ? 'No calorie goal set. Use /goal -C calories to create one.' : "Daily calorie goal: {$goal['calories']} kcal/day.");
    }
}
