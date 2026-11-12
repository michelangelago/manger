<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class FoodGroupRepository extends Repository
{
    protected const TABLE = 'food_groups';
    protected const COLUMNS = ['name'];

    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name FROM food_groups WHERE name = :name COLLATE NOCASE ORDER BY id LIMIT 1'
        );
        $statement->execute(['name' => $name]);
        $group = $statement->fetch(PDO::FETCH_ASSOC);

        return $group === false ? null : $group;
    }
}
