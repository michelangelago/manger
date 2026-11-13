<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Shows usage for bare command' => static fn (TestContext $t) => $t->command('/dfoods', 'Consumed foods:'),
    'Requires registration before accessing entries' => static function (TestContext $t): void {
        $t->command('/dfoods -I', 'Send /start');
        $t->command('/dfoods -C Apple 150', 'Send /start');
        $t->check($t->daily->index() === [], 'Unregistered command created a daily log');
    },
    'Creates entries with food names and reuses one daily log' => static function (): void {
        $t = new TestContext(static fn () => new DateTimeImmutable('2026-11-13 12:00:00-03:00'));
        $t->actAs(123);
        $t->command('/food -C "Green apple" 52 fruits', 'Food created!');
        $t->command('/dfoods -C "GREEN APPLE" 150', ['Food entry created!', 'Food: Green apple', 'Quantity: 150 g', 'Calories: 78.00 kcal', '2026-11-13 12:00:00-03:00']);
        $t->command('/dfoods -C "Green apple" 100', 'Food entry created!');
        $logs = $t->daily->index();
        $entries = $t->entries->index();
        $t->check(count($logs) === 1 && $logs[0]['date'] === '2026-11-13', 'Expected one daily log');
        $t->check(count($entries) === 2 && $entries[0]['diet_id'] === $entries[1]['diet_id'], 'Entries did not reuse the log');
        $t->command('/dfoods -I', ['Green apple', '/dfoods -U', '/dfoods -D']);
        $t->command('/daily -R 2026-11-13', ['Total calories: 130.00 kcal', 'Quantity: 150 g', 'Quantity: 100 g']);
    },
    'Reads updates and deletes entries without changing their original day' => static function (): void {
        $now = new DateTimeImmutable('2026-11-13 12:00:00-03:00');
        $t = new TestContext(static function () use (&$now) { return $now; });
        $t->actAs(123);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/food -C Yogurt 60 dairy', 'Food created!');
        $t->command('/dfoods -C Apple 150', 'Food entry created!');
        $entry = $t->entries->index()[0];
        $id = $entry['id'];
        $t->command('/dfoods -R ' . $id, ['Food: Apple', 'Quantity: 150 g']);
        $now = $now->modify('+1 day');
        $t->command('/dfoods -U ' . $id . ' YOGURT 200', ['Food entry updated!', 'Food: Yogurt', 'Calories: 120.00 kcal', '2026-11-13 12:00:00-03:00']);
        $updated = $t->entries->read((int) $id);
        $t->check($updated['diet_id'] === $entry['diet_id'] && $updated['consumed_at'] === $entry['consumed_at'] && (int) $updated['quantity'] === 200, 'Update changed the date or failed to persist');
        $t->check(count($t->daily->index()) === 1, 'Update created a new daily log');
        $t->command('/dfoods -D ' . $id, 'Food entry deleted:');
        $t->command('/dfoods -R ' . $id, 'Food entry not found.');
        $t->command('/dfoods -I', 'No consumed foods registered.');
        $t->command('/daily -R 2026-11-13', ['Total calories: 0.00 kcal', 'No food entries.']);
        $t->check(count($t->daily->index()) === 1, 'Deleting an entry deleted the daily log');
    },
    'Deleting all entries and logging again reuses the same daily row' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $log = $t->daily->index()[0];
        $t->command('/dfoods -D ' . $t->entries->index()[0]['id'], 'Food entry deleted:');
        $t->command('/dfoods -C Apple 200', 'Food entry created!');
        $t->check(count($t->daily->index()) === 1 && $t->daily->index()[0]['id'] === $log['id'], 'Daily log was replaced or duplicated');
    },
    'Entries and daily logs are isolated between users' => static function (): void {
        $t = new TestContext(static fn () => new DateTimeImmutable('2026-11-13 12:00:00-03:00'));
        $t->actAs(123);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $entry = $t->entries->index()[0];
        $id = $entry['id'];
        $t->actAs(456);
        $t->command('/dfoods -I', 'No consumed foods registered.');
        $t->command('/daily -I', 'No daily logs registered.');
        $t->command('/daily -R 2026-11-13', 'Daily log not found:');
        foreach (['-R ' . $id, '-U ' . $id . ' Apple 999', '-D ' . $id] as $arguments) {
            $t->command('/dfoods ' . $arguments, 'Food entry not found.');
        }
        $t->check($t->entries->read((int) $id) === $entry, 'Another user changed the entry');
        $t->command('/dfoods -C Apple 200', 'Food entry created!');
        $t->check(count($t->daily->index()) === 2, 'Users should have separate logs on the same day');
    },
    'Rejects missing foods without creating daily logs or modifying entries' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/dfoods -C missing 100', 'Food not found:');
        $t->check($t->daily->index() === [], 'Invalid food created an empty daily log');
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $entry = $t->entries->index()[0];
        $t->command('/dfoods -U ' . $entry['id'] . ' missing 200', 'Food not found:');
        $t->check($t->entries->read((int) $entry['id']) === $entry, 'Failed update changed the entry');
    },
    'Read and delete of nonexistent entries do not create daily logs' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/dfoods -R 999', 'Food entry not found.');
        $t->command('/dfoods -D 999', 'Food entry not found.');
        $t->check($t->daily->index() === [], 'Read or delete created a daily log');
    },
];
foreach (['-C Apple 0', '-C Apple -1', '-C Apple 1.5', '-C Apple 999999999999999999999', '-C "" 100', '-C Apple', '-R 0', '-R abc', '-U 1 Apple 0', '-D -1', '-I extra', 'Unknown', '-C "broken 100'] as $arguments) {
    $tests['Rejects invalid daily food input: ' . $arguments] = static function (TestContext $t) use ($arguments): void {
        $t->actAs(123);
        $t->command('/dfoods ' . $arguments, 'Consumed foods:');
        $t->check($t->daily->index() === [] && $t->entries->index() === [], 'Invalid input changed the database');
    };
}
return $tests;
