<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Bot\Support\CommandInput;
use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use PDOException;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__, 2) . '/database/repositories/FoodRepository.php';
require_once dirname(__DIR__, 2) . '/database/repositories/FoodGroupRepository.php';
require_once dirname(__DIR__) . '/support/CommandInput.php';

final class FoodCommand
{
    public const USAGE = "Food commands:\n"
        . "/food -I\n"
        . "/food -C \"name\" calories \"group name\"\n"
        . "/food -R \"existing name\"\n"
        . "/food -U \"existing name\" \"new name\" calories \"group name\"\n"
        . "/food -D \"existing name\"\n"
        . "Enter calories per 100 grams as a non-negative integer.\n"
        . "Quote names containing spaces. View groups with /fgroup -I.";

    public function __construct(private FoodRepository $foods, private FoodGroupRepository $groups)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        if (!preg_match('/^\s*(-[ICRUD])(?:\s+(?:"[^"]*"|[^\s"]+))*\s*$/i', $arguments)) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        preg_match_all('/"[^"]*"|[^\s"]+/', $arguments, $matches);
        $tokens = array_map(static fn (string $token): string => trim(trim($token, '"')), $matches[0]);
        $operation = CommandInput::operation(array_shift($tokens));
        $expected = ['index' => 0, 'create' => 3, 'read' => 1, 'update' => 4, 'delete' => 1];

        if (count($tokens) !== $expected[$operation] || in_array('', $tokens, true)) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        if ($operation === 'index') {
            $foods = $this->foods->index();
            $listing = $foods === [] ? 'No foods registered.' : "Foods:\n" . implode("\n\n", array_map(
                fn (array $food): string => $this->describe($food),
                $foods
            ));
            $bot->sendMessage($listing . "\n\n" . self::USAGE);
            return;
        }

        $food = $this->foods->findByName($tokens[0]);
        if ($operation !== 'create' && $food === null) {
            $bot->sendMessage("Food not found: {$tokens[0]}");
            return;
        }

        if ($operation === 'read') {
            $bot->sendMessage($this->describe($food));
            return;
        }

        if ($operation === 'delete') {
            try {
                $this->foods->delete((int) $food['id']);
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 19 || !str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                    throw $exception;
                }
                $bot->sendMessage('This food is used by daily food entries and cannot be deleted.');
                return;
            }
            $bot->sendMessage("Food deleted: {$food['name']}");
            return;
        }

        $values = $operation === 'update' ? array_slice($tokens, 1) : $tokens;
        [$name, $calories, $groupName] = $values;
        $energy = preg_match('/^[0-9]+$/', $calories)
            ? filter_var(ltrim($calories, '0') ?: '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])
            : false;
        if ($energy === false) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        $existing = $this->foods->findByName($name);
        if ($existing !== null && ($operation === 'create' || $existing['id'] !== $food['id'])) {
            $bot->sendMessage('A food with that name already exists.');
            return;
        }

        $group = $this->groups->findByName($groupName);
        if ($group === null) {
            $bot->sendMessage("Group not found: {$groupName}\nView groups with /fgroup -I.");
            return;
        }

        $data = ['name' => $name, 'energy' => $energy, 'food_group_id' => (int) $group['id']];
        if ($operation === 'create') {
            $id = $this->foods->create($data);
            $message = 'Food created!';
        } else {
            $id = (int) $food['id'];
            $this->foods->update($id, $data);
            $message = 'Food updated!';
        }
        $bot->sendMessage($message . "\n" . $this->describe(['id' => $id, ...$data]));
    }

    private function describe(array $food): string
    {
        $group = $this->groups->read((int) $food['food_group_id']);
        return "ID: {$food['id']}\n"
            . "Name: {$food['name']}\n"
            . "Calories per 100 g: {$food['energy']}\n"
            . "Group: {$group['name']}";
    }
}
