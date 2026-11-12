<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $statement = $pdo->prepare(
        'INSERT INTO food_groups (name) SELECT :name WHERE NOT EXISTS '
        . '(SELECT 1 FROM food_groups WHERE name = :name COLLATE NOCASE)'
    );

    foreach (['fruits', 'vegetables', 'grains', 'protein', 'dairy', 'fats'] as $name) {
        $statement->execute(['name' => $name]);
    }
};
