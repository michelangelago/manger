<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
        CREATE TABLE food_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL
        );

        CREATE TABLE foods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            energy INTEGER NOT NULL,
            food_group_id INTEGER NOT NULL,
            FOREIGN KEY (food_group_id) REFERENCES food_groups (id)
        );

        CREATE TABLE daily (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users (id)
        );

        CREATE TABLE daily_foods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            diet_id INTEGER NOT NULL,
            food_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            consumed_at DATETIME NOT NULL,
            FOREIGN KEY (diet_id) REFERENCES daily (id),
            FOREIGN KEY (food_id) REFERENCES foods (id)
        );

        CREATE INDEX foods_food_group_id_index ON foods (food_group_id);
        CREATE INDEX daily_user_id_index ON daily (user_id);
        CREATE INDEX daily_foods_diet_id_index ON daily_foods (diet_id);
        CREATE INDEX daily_foods_food_id_index ON daily_foods (food_id);
        SQL);
};
