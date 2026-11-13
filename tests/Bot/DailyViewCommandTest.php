<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$fixture = static function (): TestContext {
    // UTC is already Saturday, but Recife is still Friday.
    $t = new TestContext(static fn () => new DateTimeImmutable('2026-11-14 01:00:00+00:00'));
    $t->actAs(123);
    $userId = (int) $t->users->findByTelegramId(123)['id'];
    $group = $t->groups->findByName('fruits');
    $apple = $t->foods->create(['name' => 'Apple', 'energy' => 52, 'food_group_id' => $group['id']]);
    $yogurt = $t->foods->create(['name' => 'Yogurt', 'energy' => 60, 'food_group_id' => $group['id']]);
    foreach (['2025-12-31', '2026-01-01', '2026-11-08', '2026-11-09', '2026-11-16', '2026-11-30', '2026-12-31', '2027-01-01'] as $date) {
        $t->entries->registerConsumption($userId, $apple, 100, new DateTimeImmutable($date . ' 12:00:00-03:00'));
    }
    foreach ([[150, $apple], [50, $apple], [100, $yogurt]] as [$grams, $food]) {
        $t->entries->registerConsumption($userId, $food, $grams, new DateTimeImmutable('2026-11-13 12:00:00-03:00'));
    }
    $t->entries->registerConsumption($userId, $yogurt, 200, new DateTimeImmutable('2026-11-15 23:59:59-03:00'));
    $t->entries->registerConsumption($userId, $yogurt, 100, new DateTimeImmutable('2026-12-01 00:00:00-03:00'));
    return $t;
};

$tests = [
    'Requires registration' => static fn (TestContext $t) => $t->command('/dview', 'Send /start'),
    'Defaults to local today and merges repeated foods into compact rows' => static function () use ($fixture): void {
        $t = $fixture();
        $reply = $t->command('/dview', ['Total: 164 kcal', 'Day · 2026-11-13', 'Apple · 200 g · 104 kcal', 'Yogurt · 100 g · 60 kcal']);
        $t->check(substr_count($reply, 'Apple') === 1, 'Repeated food was not aggregated');
        foreach (['Entry ID:', 'Consumed at:', 'Group:', '/dview'] as $detail) {
            $t->check(!str_contains($reply, $detail), 'Summary includes unnecessary details');
        }
        $t->check(strpos($reply, 'Total:') < strpos($reply, 'Apple'), 'Total is not above the food list');
    },
    'Accepts quoted date and day flag in either order' => static function () use ($fixture): void {
        $t = $fixture();
        foreach (['/dview "2026-11-13"', '/dview -D 2026-11-13', '/dview "2026-11-13" -d'] as $input) {
            $t->command($input, ['Total: 164 kcal', 'Day · 2026-11-13']);
        }
    },
    'Week includes Monday and Sunday but excludes adjacent days' => static function () use ($fixture): void {
        $t = $fixture();
        $t->command('/dview -W', ['Total: 336 kcal', 'Week · 2026-11-09 to 2026-11-15', 'Apple · 300 g · 156 kcal', 'Yogurt · 300 g · 180 kcal']);
        $t->command('/dview -W 2026-11-15', 'Total: 336 kcal');
    },
    'Month uses the entire calendar month' => static function () use ($fixture): void {
        $t = $fixture();
        $t->command('/dview -M 2026-11-13', ['Total: 492 kcal', 'Month · 2026-11-01 to 2026-11-30']);
    },
    'Year uses the entire calendar year' => static function () use ($fixture): void {
        $t = $fixture();
        $t->command('/dview -Y', ['Total: 656 kcal', 'Year · 2026-01-01 to 2026-12-31']);
    },
    'Calendar weeks can cross year boundaries' => static function () use ($fixture): void {
        $t = $fixture();
        $t->command('/dview -W 2026-01-01', ['Total: 104 kcal', 'Week · 2025-12-29 to 2026-01-04']);
    },
    'Leap February includes the 29th and excludes March' => static function (TestContext $t): void {
        $t->actAs(123);
        $user = $t->users->findByTelegramId(123);
        $food = $t->foods->create(['name' => 'Apple', 'energy' => 52, 'food_group_id' => $t->groups->findByName('fruits')['id']]);
        foreach (['2024-02-29', '2024-03-01'] as $date) {
            $t->entries->registerConsumption((int) $user['id'], $food, 100, new DateTimeImmutable($date));
        }
        $t->command('/dview -M 2024-02-29', ['Total: 52 kcal', 'Month · 2024-02-01 to 2024-02-29']);
    },
    'Empty summary shows zero without creating a log' => static function (TestContext $t): void {
        $t->actAs(123);
        $t->command('/dview 2026-11-13', ['Total: 0 kcal', 'No foods logged.']);
        $t->check($t->daily->index() === [], 'View created a daily log');
    },
    'Preserves fractional calories from small quantities' => static function (TestContext $t): void {
        $t->actAs(123);
        $user = $t->users->findByTelegramId(123);
        $food = $t->foods->create(['name' => 'Apple', 'energy' => 52, 'food_group_id' => $t->groups->findByName('fruits')['id']]);
        $t->entries->registerConsumption((int) $user['id'], $food, 1, new DateTimeImmutable('2026-11-13'));
        $t->command('/dview 2026-11-13', ['Total: 0.52 kcal', 'Apple · 1 g · 0.52 kcal']);
    },
    'Summaries include only the current user' => static function () use ($fixture): void {
        $t = $fixture();
        $t->actAs(456);
        $t->command('/dview', ['Total: 0 kcal', 'No foods logged.']);
        $t->command('/dfoods -C Apple 100', 'Food entry created!');
        $t->command('/dview', ['Total: 52 kcal', 'Apple · 100 g']);
        $t->actAs(123);
        $t->command('/dview', 'Total: 164 kcal');
    },
    'Large summaries stay compact while totals include all foods' => static function (TestContext $t): void {
        $t->actAs(123);
        $user = $t->users->findByTelegramId(123);
        $group = $t->groups->findByName('fruits');
        for ($i = 0; $i < 20; $i++) {
            $food = $t->foods->create(['name' => 'Food ' . $i . str_repeat('x', 200), 'energy' => 100, 'food_group_id' => $group['id']]);
            $t->entries->registerConsumption((int) $user['id'], $food, 100, new DateTimeImmutable('2026-11-13'));
        }
        $reply = $t->command('/dview 2026-11-13', ['Total: 2000 kcal', '+5 more foods (included in total)']);
        $t->check(substr_count($reply, '100 g') === 15 && strlen($reply) < 4096, 'Summary is not compact');
    },
];
foreach (['-I', '-C', '-D -W', '2026-11-13 2026-11-14', '-W -W', '-M 2026-11-13 extra', '2026-02-30', 'today', '"broken', '""'] as $arguments) {
    $tests['Rejects invalid view input: ' . $arguments] = static function (TestContext $t) use ($arguments): void {
        $t->command('/dview ' . $arguments, '/dview [-D|-W|-M|-Y] [YYYY-MM-DD]');
    };
}
return $tests;
