<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Read without date uses today in the configured timezone and follows midnight' => static function (): void {
        $now = new DateTimeImmutable('2026-11-14 01:00:00+00:00');
        $t = new TestContext(static function () use (&$now) { return $now; });
        $t->actAs(123);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 150', 'Food entry created!');
        $t->command('/daily -R', ['Daily log: 2026-11-13', 'Total calories: 78.00 kcal']);
        $now = new DateTimeImmutable('2026-11-14 03:00:00+00:00');
        $t->command('/daily -R', 'Daily log not found: 2026-11-14');
        $t->check(count($t->daily->index()) === 1, 'Reading today created a daily log');
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $t->command('/daily -R', ['Daily log: 2026-11-14', 'Total calories: 52.00 kcal']);
        $t->command('/daily -R 2026-11-13', ['Daily log: 2026-11-13', 'Total calories: 78.00 kcal']);
    },
    'Bare command shows usage' => static fn (TestContext $t) => $t->command('/daily', ['/daily -I', '/daily -R']),
    'Requires registration' => static fn (TestContext $t) => $t->command('/daily -I', 'Send /start'),
    'Lists only existing logs and reading a missing date does not create one' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/daily -I', ['No daily logs registered.', '/daily -R']);
        $t->command('/daily -R 2026-11-13', 'Daily log not found:');
        $t->check($t->daily->index() === [], 'Viewing logs created a daily row');
    },
];
foreach (['-C Today', '-U Today', '-D Today', '-R 2026-02-30', '-R 2026-2-01', '-R yesterday', '-R 2026-11-13 extra', '-I extra'] as $arguments) {
    $tests['Rejects unsupported operation or invalid date: ' . $arguments] = static fn (TestContext $t) => $t->command('/daily ' . $arguments, '/daily -R [YYYY-MM-DD]');
}
return $tests;
