<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Logs consumption and shares the daily row with dfoods' => static function (): void {
        $t = new TestContext(static fn () => new DateTimeImmutable('2026-11-13 12:00:00-03:00'));
        $t->actAs(123);
        $t->command('/food -C "Green apple" 52 fruits', 'Food created!');
        $t->command('/eat "GREEN APPLE" 150', ['Food entry created!', 'Food: Green apple', 'Quantity: 150 g', 'Calories: 78.00 kcal']);
        $t->command('/dfoods -C "Green apple" 100', 'Food entry created!');
        $t->check(count($t->daily->index()) === 1 && count($t->entries->index()) === 2, 'Shortcut did not share the daily log');
        $t->command('/dview', ['Total: 130 kcal', 'Green apple · 250 g']);
    },
    'Requires registration and rejects nonexistent foods without creating logs' => static function (TestContext $t): void {
        $t->command('/eat Apple 100', 'Send /start');
        $t->actAs(123);
        $t->command('/eat missing 100', 'Food not found: missing');
        $t->check($t->daily->index() === [] && $t->entries->index() === [], 'Failed shortcut created records');
    },
];
foreach (['', 'Apple', 'Apple 0', 'Apple -1', 'Apple 1.5', '"" 100', '"broken 100', 'Apple 100 extra', 'Apple abc', 'Apple 999999999999999999999999'] as $arguments) {
    $tests['Shows shortcut usage for invalid input: ' . $arguments] = static function (TestContext $t) use ($arguments): void {
        $t->actAs(123);
        $t->command(rtrim('/eat ' . $arguments), '/eat "food name" grams');
        $t->check($t->daily->index() === [] && $t->entries->index() === [], 'Invalid shortcut changed the database');
    };
}
return $tests;
