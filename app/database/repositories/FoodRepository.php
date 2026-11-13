<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class FoodRepository extends Repository
{
    protected const TABLE = 'foods';
    protected const COLUMNS = ['name', 'energy', 'food_group_id'];

    public function indexByGroup(?int $groupId = null): array
    {
        $sql = 'SELECT g.id AS group_id, g.name AS group_name, f.name AS food_name, f.energy '
            . 'FROM food_groups g LEFT JOIN foods f ON f.food_group_id = g.id';
        if ($groupId !== null) {
            $sql .= ' WHERE g.id = :group_id';
        }
        $statement = $this->pdo->prepare($sql . ' ORDER BY g.name COLLATE NOCASE, g.id, f.name COLLATE NOCASE, f.id');
        $statement->execute($groupId === null ? [] : ['group_id' => $groupId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM foods WHERE name = :name COLLATE NOCASE ORDER BY id LIMIT 1'
        );
        $statement->execute(['name' => $name]);
        $food = $statement->fetch(PDO::FETCH_ASSOC);

        return $food === false ? null : $food;
    }
}
