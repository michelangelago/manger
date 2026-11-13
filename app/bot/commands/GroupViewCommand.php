<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Bot\Support\GroupFoodView;
use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__) . '/support/CommandInput.php';
require_once dirname(__DIR__) . '/support/GroupFoodView.php';
require_once dirname(__DIR__, 2) . '/database/repositories/FoodGroupRepository.php';
require_once dirname(__DIR__, 2) . '/database/repositories/FoodRepository.php';

final class GroupViewCommand
{
    public const USAGE = "Foods by group:\n/gview [\"group name\"]\nOmit the group to view all groups. Calories are per 100 g.";

    public function __construct(private FoodGroupRepository $groups, private FoodRepository $foods)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        $tokens = trim($arguments) === '' ? [] : CommandInput::tokens($arguments);
        if ($tokens === null || count($tokens) > 1) {
            $bot->sendMessage(self::USAGE);
            return;
        }
        $groupId = null;
        if ($tokens !== []) {
            $group = $this->groups->findByName($tokens[0]);
            if ($group === null) {
                $bot->sendMessage("Food group not found: {$tokens[0]}");
                return;
            }
            $groupId = (int) $group['id'];
        }
        foreach (GroupFoodView::pages($this->foods->indexByGroup($groupId)) as $page) {
            $bot->sendMessage($page);
        }
    }
}
