<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Index shows foods and available operations' => static function (TestContext $t): void {
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/food -I', ['Name: Apple', 'Calories per 100 g: 52', 'Group: fruits', '/food -C', '/food -R', '/food -U', '/food -D']);
    },
    'Empty index still shows available operations' => static fn (TestContext $t) => $t->command('/food -I', ['No foods registered.', '/food -C', '/food -R', '/food -U', '/food -D']),
    'Reads, updates and deletes foods by name ignoring case' => static function (TestContext $t): void {
        $t->command('/food -C "Green apple" 52 fruits', 'Food created!');
        $original = $t->foods->findByName('Green apple');
        $t->command('/food -R "GREEN APPLE"', ['Name: Green apple', 'Calories per 100 g: 52', 'Group: fruits']);
        $t->command('/food -U "Green apple" "Apple yogurt" 90 DAIRY', ['Food updated!', 'Name: Apple yogurt', 'Calories per 100 g: 90', 'Group: dairy']);
        $updated = $t->foods->findByName('Apple yogurt');
        $t->check($t->foods->findByName('Green apple') === null && $updated['id'] === $original['id'] && (int) $updated['energy'] === 90 && (int) $updated['food_group_id'] === (int) $t->groups->findByName('dairy')['id'], 'Update did not persist the expected values');
        $t->command('/food -D "APPLE YOGURT"', 'Food deleted: Apple yogurt');
        $t->check($t->foods->index() === [], 'Food was not deleted');
    },
    'Rejects duplicate names during create and update' => static function (TestContext $t): void {
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/food -C APPLE 60 fruits', 'already exists');
        $t->command('/food -C Banana 89 fruits', 'Food created!');
        $t->command('/food -U Banana apple 10 dairy', 'already exists');
        $t->check(count($t->foods->index()) === 2 && (int) $t->foods->findByName('Banana')['energy'] === 89, 'Duplicate operation changed foods');
    },
    'Update permits keeping the same name and rejects invalid values without changes' => static function (TestContext $t): void {
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/food -U Apple Apple 60 fruits', 'Food updated!');
        $before = $t->foods->index();
        $t->command('/food -U Apple Apple 70 missing', 'Group not found:');
        $t->command('/food -U Apple Apple -1 fruits', 'Food commands:');
        $t->check($t->foods->index() === $before, 'Invalid update changed foods');
    },
    'Read, update and delete report missing foods' => static function (TestContext $t): void {
        foreach (['-R missing', '-U missing New 10 fruits', '-D missing'] as $arguments) {
            $t->command('/food ' . $arguments, 'Food not found:');
        }
    },
    'Prevents deletion of foods referenced by daily entries' => static function (TestContext $t): void {
        $t->users->save(123, 'Maria');
        $user = $t->users->findByTelegramId(123);
        $daily = new App\Database\Repositories\DailyRepository($t->pdo);
        $entries = new App\Database\Repositories\DailyFoodRepository($t->pdo);
        $dailyId = $daily->create(['name' => 'Today', 'user_id' => $user['id']]);
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $food = $t->foods->findByName('Apple');
        $entries->create(['diet_id' => $dailyId, 'food_id' => $food['id'], 'quantity' => 100, 'consumed_at' => '2026-11-12 12:00:00']);
        $t->command('/food -D Apple', 'used by daily food entries');
        $t->check($t->foods->findByName('Apple') !== null, 'Referenced food was deleted');
    },
    'Command without arguments shows usage' => static fn (TestContext $t) => $t->command('/food', 'Food commands:'),
    'Registers food and resolves the group by name' => static function (TestContext $t): void {
        $t->command('/food -C "Green apple" 52 FRUITS', ['Food created!', 'Green apple', 'Calories per 100 g: 52', 'Group: fruits']);
        $food = $t->foods->index()[0];
        $fruit = $t->groups->findByName('fruits');
        $t->check($food['name'] === 'Green apple' && (int) $food['energy'] === 52 && (int) $food['food_group_id'] === (int) $fruit['id'], 'Incorrect food data or foreign key');
    },
    'Accepts zero calories, spaces, and apostrophes' => static function (TestContext $t): void {
        $t->groups->create(['name' => "Chef's choice"]);
        $t->command('/food -C "Chef\'s special" 0 "Chef\'s choice"', ['Food created!', 'Calories per 100 g: 0', "Group: Chef's choice"]);
        $t->check(count($t->foods->index()) === 1, 'Food was not saved');
    },
    'Rejects a missing group or an ID instead of a name' => static function (TestContext $t): void {
        $t->command('/food -C Banana 89 missing', 'Group not found:');
        $t->command('/food -C Banana 89 ' . $t->groups->findByName('fruits')['id'], 'Group not found:');
        $t->check($t->foods->index() === [], 'Invalid food was saved');
    },
];
require_once dirname(__DIR__, 2) . '/app/database/repositories/DailyRepository.php';
require_once dirname(__DIR__, 2) . '/app/database/repositories/DailyFoodRepository.php';
foreach (['/food -I extra', '/food -R', '/food -R Apple extra', '/food -D', '/food -U Apple', '/food -U Apple New 52', '/food Unknown', '/food Apple 52 fruits'] as $input) {
    $tests['Rejects invalid operation arguments: ' . $input] = static fn (TestContext $t) => $t->command($input, 'Food commands:');
}
foreach (['/food -C Banana -1 fruits', '/food -C Banana 2.5 fruits', '/food -C Banana abc fruits', '/food -C "" 52 fruits', '/food -C Banana 52 ""', '/food -C Banana 52', '/food -C Banana 52 fruits extra', '/food -C "broken 52 fruits', '/food -C Banana 99999999999999999999999999 fruits'] as $input) {
    $tests['Rejects invalid input: ' . $input] = static function (TestContext $t) use ($input): void {
        $t->command($input, 'Food commands:');
        $t->check($t->foods->index() === [], 'Invalid input inserted a food');
    };
}
return $tests;
