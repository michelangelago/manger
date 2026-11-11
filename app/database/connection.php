<?php

declare(strict_types=1);

$storageDirectory = dirname(__DIR__, 2) . '/storage';

if (!is_dir($storageDirectory)
    && !mkdir($storageDirectory, 0775, true)
    && !is_dir($storageDirectory)) {
    throw new RuntimeException('Unable to create the database storage directory.');
}

$pdo = new PDO('sqlite:' . $storageDirectory . '/database.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('PRAGMA busy_timeout = 5000');

return $pdo;
