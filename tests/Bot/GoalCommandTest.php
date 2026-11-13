<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Bare command shows goal syntax' => static fn (TestContext $t) => $t->command('/goal', ['/goal -C', '/goal -R', '/goal -U', '/goal -D']),
    'Requires registration for every operation' => static function (TestContext $t): void {
        foreach (['-C 2000', '-R', '-U 1800', '-D'] as $arguments) {
            $t->command('/goal ' . $arguments, 'Send /start');
        }
        $t->check($t->goals->index() === [], 'Unregistered user created a goal');
    },
    'Creates reads updates and deletes a personal goal' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/goal -C 2000', 'Daily goal created: 2000 kcal/day.');
        $t->command('/goal -R', 'Daily calorie goal: 2000 kcal/day.');
        $t->command('/goal -U 1800', 'Daily goal updated: 1800 kcal/day.');
        $t->command('/goal -R', 'Daily calorie goal: 1800 kcal/day.');
        $t->command('/goal -U 1800', 'Daily goal updated: 1800 kcal/day.');
        $t->command('/goal -D', 'Daily calorie goal deleted.');
        $t->check($t->goals->index() === [], 'Goal was not deleted');
        $t->command('/goal -C 2100', 'Daily goal created: 2100 kcal/day.');
    },
    'Create does not overwrite an existing goal' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/goal -C 2000', 'Daily goal created:');
        $t->command('/goal -C 1800', 'You already have a goal.');
        $t->command('/goal -R', '2000 kcal/day.');
        $t->check(count($t->goals->index()) === 1, 'Duplicate goal created');
    },
    'Missing goals give clear replies and update does not create' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/goal -R', 'No calorie goal set.');
        $t->command('/goal -U 1800', 'No calorie goal set.');
        $t->command('/goal -D', 'No calorie goal set.');
        $t->check($t->goals->index() === [], 'Read update or delete created a goal');
    },
    'Goals are isolated between users' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/goal -C 2000', 'Daily goal created:');
        $t->actAs(456);
        $t->command('/goal -R', 'No calorie goal set.');
        $t->command('/goal -U 900', 'No calorie goal set.');
        $t->command('/goal -D', 'No calorie goal set.');
        $t->command('/goal -C 1800', 'Daily goal created:');
        $t->actAs(123);
        $t->command('/goal -R', '2000 kcal/day.');
        $t->command('/goal -D', 'Daily calorie goal deleted.');
        $t->actAs(456);
        $t->command('/goal -R', '1800 kcal/day.');
    },
    'Accepts lowercase flags' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/goal -c 2000', 'Daily goal created:');
        $t->command('/goal -r', '2000 kcal/day.');
        $t->command('/goal -u 1900', 'Daily goal updated:');
        $t->command('/goal -d', 'Daily calorie goal deleted.');
    },
    'Today summary shows goal remaining and excess; deleting goal hides it' => static function (): void {
        $t = new TestContext(static fn () => new DateTimeImmutable('2026-11-14 01:00:00+00:00'));
        $t->actAs(123);
        $t->command('/goal -C 2000', 'Daily goal created:');
        $t->command('/dview', ['Goal: 2000 kcal', 'Remaining: 2000 kcal', 'No foods logged.']);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/dfoods -C Apple 150', 'Food entry created!');
        $t->command('/dview', ['Total: 78 kcal', 'Goal: 2000 kcal', 'Remaining: 1922 kcal']);
        $t->command('/goal -U 50', 'Daily goal updated:');
        $t->command('/dview -D 2026-11-13', ['Goal: 50 kcal', 'Over goal: 28 kcal']);
        $t->command('/goal -U 78', 'Daily goal updated:');
        $t->command('/dview', ['Goal: 78 kcal', 'Remaining: 0 kcal']);
        foreach (['/dview -W', '/dview -M', '/dview -Y', '/dview 2026-11-12'] as $input) {
            $reply = $t->command($input, 'Total:');
            $t->check(!str_contains($reply, 'Goal:'), 'Current daily goal applied to another period');
        }
        $t->command('/goal -D', 'Daily calorie goal deleted.');
        $reply = $t->command('/dview', 'Total: 78 kcal');
        $t->check(!str_contains($reply, 'Goal:'), 'Deleted goal is still shown');
    },
];
foreach (['-I', '-X', '-C', '-U', '-R 2000', '-D 1', '-C 0', '-C -1', '-C 2.5', '-C abc', '-C 99999999999999999999', '-U 0', '-U 1800 extra', 'Create 2000', '"broken'] as $arguments) {
    $tests['Rejects invalid goal input: ' . $arguments] = static function (TestContext $t) use ($arguments): void {
        $t->actAs(123);
        $t->command('/goal ' . $arguments, 'Daily calorie goal:');
        $t->check($t->goals->index() === [], 'Invalid input created a goal');
    };
}
return $tests;
