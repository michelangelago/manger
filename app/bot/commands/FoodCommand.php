<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__, 2) . '/database/repositories/FoodRepository.php';
require_once dirname(__DIR__, 2) . '/database/repositories/FoodGroupRepository.php';

final class FoodCommand
{
    public const USAGE = "Cadastrar alimento:\n"
        . "/food \"Nome\" calorias \"Nome do grupo\"\n"
        . "Exemplo: /food \"Maçã\" 52 fruits\n"
        . "Informe as calorias como um número inteiro maior ou igual a zero.\n"
        . "Use aspas para nomes com espaços. Veja os grupos com /fgroup Index.";

    public function __construct(private FoodRepository $foods, private FoodGroupRepository $groups)
    {
    }

    public function __invoke(Nutgram $bot, string $arguments = ''): void
    {
        if (!preg_match('/^\s*("[^"]*"|[^\s"]+)\s+([0-9]+)\s+("[^"]*"|[^\s"]+)\s*$/', $arguments, $matches)) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        $name = trim(trim($matches[1], '"'));
        $groupName = trim(trim($matches[3], '"'));
        $energy = filter_var(ltrim($matches[2], '0') ?: '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($name === '' || $groupName === '' || $energy === false) {
            $bot->sendMessage(self::USAGE);
            return;
        }

        $group = $this->groups->findByName($groupName);

        if ($group === null) {
            $bot->sendMessage("Grupo não encontrado: {$groupName}\nConsulte os grupos com /fgroup Index.");
            return;
        }

        $id = $this->foods->create([
            'name' => $name,
            'energy' => $energy,
            'food_group_id' => (int) $group['id'],
        ]);

        $bot->sendMessage(
            "Alimento cadastrado!\n"
            . "ID: {$id}\n"
            . "Nome: {$name}\n"
            . "Calorias: {$energy}\n"
            . "Grupo: {$group['name']}"
        );
    }
}
