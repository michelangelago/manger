<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    // Existing logs keep their names and entries; new dated logs use this key.
    $pdo->exec('ALTER TABLE daily ADD COLUMN date TEXT');
    $pdo->exec('CREATE UNIQUE INDEX daily_user_date_unique ON daily (user_id, date)');
};
