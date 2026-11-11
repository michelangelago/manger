<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec('ALTER TABLE users ADD COLUMN username TEXT');
};
