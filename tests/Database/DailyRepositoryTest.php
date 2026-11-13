<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Local midnight creates one new daily log while UTC midnight does not' => static function (): void {
        $now = new DateTimeImmutable('2026-11-13 23:59:00+00:00');
        $t = new TestContext(static function () use (&$now) { return $now; });
        $t->actAs(123);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $now = new DateTimeImmutable('2026-11-14 00:01:00+00:00');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $t->check(count($t->daily->index()) === 1, 'UTC midnight split a local day');
        $now = new DateTimeImmutable('2026-11-14 03:00:00+00:00');
        $t->command('/dfoods -C Apple 100', ['Food entry created!', '2026-11-14 00:00:00-03:00']);
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $t->check(array_column($t->daily->index(), 'date') === ['2026-11-13', '2026-11-14'], 'Expected one log for each local day');
        $t->command('/daily -I', ['2026-11-13', '2026-11-14']);
    },
    'Database enforces uniqueness for user and date' => static function (TestContext $t): void {
        $t->actAs(123);
        $user = $t->users->findByTelegramId(123);
        $first = $t->daily->getOrCreateForDate((int) $user['id'], '2026-11-13');
        $again = $t->daily->getOrCreateForDate((int) $user['id'], '2026-11-13');
        $t->check($first['id'] === $again['id'], 'Get or create returned a different row');
        try {
            $t->daily->create(['name' => 'Duplicate', 'user_id' => $user['id'], 'date' => '2026-11-13']);
            throw new RuntimeException('Database allowed a duplicate daily log');
        } catch (PDOException $exception) {
            $t->check(str_contains($exception->getMessage(), 'UNIQUE constraint failed'), 'Unexpected database error');
        }
    },
    'Failed consumption insert rolls back its newly created daily log' => static function (TestContext $t): void {
        $t->actAs(123);
        $user = $t->users->findByTelegramId(123);
        try {
            $t->entries->registerConsumption((int) $user['id'], 999, 100, new DateTimeImmutable('2026-11-13'));
            throw new RuntimeException('Invalid foreign key was accepted');
        } catch (PDOException $exception) {
            $t->check(str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed'), 'Unexpected database error');
        }
        $t->check($t->daily->index() === [] && !$t->pdo->inTransaction(), 'Failed operation left a daily log or open transaction');
    },
    'Migration preserves existing logs and consumed foods' => static function (TestContext $t): void {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        foreach (['001_create_users.php', '002_create_food_tables.php'] as $file) {
            $migration = require dirname(__DIR__, 2) . '/app/database/migrations/' . $file;
            $migration($pdo);
        }
        $pdo->exec("INSERT INTO users (telegram_id, name) VALUES (123, 'Maria'); INSERT INTO food_groups (name) VALUES ('fruits'); INSERT INTO foods (name, energy, food_group_id) VALUES ('Apple', 52, 1); INSERT INTO daily (name, user_id) VALUES ('Old log', 1); INSERT INTO daily_foods (diet_id, food_id, quantity, consumed_at) VALUES (1, 1, 100, '2026-11-12 12:00:00')");
        $migration = require dirname(__DIR__, 2) . '/app/database/migrations/004_add_daily_date.php';
        $migration($pdo);
        $t->check($pdo->query('SELECT name FROM daily')->fetchColumn() === 'Old log', 'Migration changed the legacy log');
        $t->check((int) $pdo->query('SELECT COUNT(*) FROM daily_foods')->fetchColumn() === 1, 'Migration removed consumed foods');
    },
];
