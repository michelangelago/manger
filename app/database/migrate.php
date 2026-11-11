<?php

declare(strict_types=1);

$pdo = require __DIR__ . '/connection.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY)');
$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    $pdo->beginTransaction();

    try {
        $check = $pdo->prepare('SELECT 1 FROM migrations WHERE name = :name');
        $check->execute(['name' => $name]);

        if ($check->fetchColumn() !== false) {
            $pdo->commit();
            continue;
        }

        $migration = require $file;
        $migration($pdo);

        $record = $pdo->prepare('INSERT INTO migrations (name) VALUES (:name)');
        $record->execute(['name' => $name]);
        $pdo->commit();

        echo "Migrated: {$name}\n";
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
