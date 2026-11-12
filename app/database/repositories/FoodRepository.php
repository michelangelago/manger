<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class FoodRepository extends Repository
{
    protected const TABLE = 'foods';
    protected const COLUMNS = ['name', 'energy', 'food_group_id'];

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
