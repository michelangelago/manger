<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
        CREATE TABLE calorie_goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            calories INTEGER NOT NULL CHECK (typeof(calories) = 'integer' AND calories > 0),
            FOREIGN KEY (user_id) REFERENCES users (id)
        )
        SQL);
};
