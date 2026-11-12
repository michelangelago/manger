<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Database\Repositories\FoodGroupRepository;
use PDOException;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__, 2) . '/database/repositories/FoodGroupRepository.php';

final class FoodGroupCommand
{
    public const USAGE = "Food group commands:\n"
        . "/fgroup Index\n"
        . "/fgroup Create \"new name\"\n"
        . "/fgroup Read \"existing name\"\n"
        . "/fgroup Update \"existing name\" \"new name\"\n"
        . "/fgroup Delete \"existing name\"";

    public function __construct(private FoodGroupRepository $groups)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        // Names may be a single word or a double-quoted phrase.
        if (!preg_match('/^\s*(Index|Create|Delete|Read|Update)(?:\s+("[^"]*"|[^\s"]+))?(?:\s+("[^"]*"|[^\s"]+))?\s*$/i', $arguments, $matches)) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        $operation = strtolower($matches[1]);
        $names = array_map(static fn (string $name): string => trim(trim($name, '"')), array_slice($matches, 2));
        $expected = $operation === 'index' ? 0 : ($operation === 'update' ? 2 : 1);

        if (count($names) !== $expected || in_array('', $names, true)) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        if ($operation === 'index') {
            $groups = $this->groups->index();
            $listing = $groups === [] ? 'No food groups registered.' : "Food groups:\n" . implode("\n", array_map(
                static fn (array $group): string => "{$group['id']}: {$group['name']}",
                $groups
            ));
            $bot->sendMessage($listing . "\n\n" . self::USAGE);
            return;
        }

        $group = $this->groups->findByName($names[0]);

        if ($operation === 'create') {
            if ($group !== null) {
                $bot->sendMessage('A food group with that name already exists.');
                return;
            }

            $id = $this->groups->create(['name' => $names[0]]);
            $bot->sendMessage("Food group created.\nID: {$id}\nName: {$names[0]}");
            return;
        }

        if ($group === null) {
            $bot->sendMessage("Food group not found: {$names[0]}");
            return;
        }

        if ($operation === 'read') {
            $bot->sendMessage("ID: {$group['id']}\nName: {$group['name']}");
            return;
        }

        if ($operation === 'update') {
            $existing = $this->groups->findByName($names[1]);
            if ($existing !== null && $existing['id'] !== $group['id']) {
                $bot->sendMessage('A food group with that name already exists.');
                return;
            }

            $this->groups->update((int) $group['id'], ['name' => $names[1]]);
            $bot->sendMessage("Food group updated.\nID: {$group['id']}\nName: {$names[1]}");
            return;
        }

        try {
            $this->groups->delete((int) $group['id']);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 19 || !str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw $exception;
            }

            $bot->sendMessage('This food group is used by foods and cannot be deleted.');
            return;
        }

        $bot->sendMessage("Food group deleted: {$group['name']}");
    }
}
