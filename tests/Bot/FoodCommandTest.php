<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Index shows foods and available operations' => static function (TestContext $t): void {
        $t->command('/food Create Apple 52 fruits', 'Food created!');
        $t->command('/food Index', ['Name: Apple', 'Calories per 100 g: 52', 'Group: fruits', '/food Create', '/food Read', '/food Update', '/food Delete']);
    },
    'Empty index still shows available operations' => static fn (TestContext $t) => $t->command('/food Index', ['No foods registered.', '/food Create', '/food Read', '/food Update', '/food Delete']),
    'Reads, updates and deletes foods by name ignoring case' => static function (TestContext $t): void {
        $t->command('/food Create "Green apple" 52 fruits', 'Food created!');
        $original = $t->foods->findByName('Green apple');
        $t->command('/food read "GREEN APPLE"', ['Name: Green apple', 'Calories per 100 g: 52', 'Group: fruits']);
        $t->command('/food Update "Green apple" "Apple yogurt" 90 DAIRY', ['Food updated!', 'Name: Apple yogurt', 'Calories per 100 g: 90', 'Group: dairy']);
        $updated = $t->foods->findByName('Apple yogurt');
        $t->check($t->foods->findByName('Green apple') === null && $updated['id'] === $original['id'] && (int) $updated['energy'] === 90 && (int) $updated['food_group_id'] === (int) $t->groups->findByName('dairy')['id'], 'Update did not persist the expected values');
        $t->command('/food Delete "APPLE YOGURT"', 'Food deleted: Apple yogurt');
        $t->check($t->foods->index() === [], 'Food was not deleted');
    },
    'Rejects duplicate names during create and update' => static function (TestContext $t): void {
        $t->command('/food Create Apple 52 fruits', 'Food created!');
        $t->command('/food Create APPLE 60 fruits', 'already exists');
        $t->command('/food Create Banana 89 fruits', 'Food created!');
        $t->command('/food Update Banana apple 10 dairy', 'already exists');
        $t->check(count($t->foods->index()) === 2 && (int) $t->foods->findByName('Banana')['energy'] === 89, 'Duplicate operation changed foods');
    },
    'Update permits keeping the same name and rejects invalid values without changes' => static function (TestContext $t): void {
        $t->command('/food Create Apple 52 fruits', 'Food created!');
        $t->command('/food Update Apple Apple 60 fruits', 'Food updated!');
        $before = $t->foods->index();
        $t->command('/food Update Apple Apple 70 missing', 'Group not found:');
        $t->command('/food Update Apple Apple -1 fruits', 'Food commands:');
        $t->check($t->foods->index() === $before, 'Invalid update changed foods');
    },
    'Read, update and delete report missing foods' => static function (TestContext $t): void {
        foreach (['Read missing', 'Update missing New 10 fruits', 'Delete missing'] as $arguments) {
            $t->command('/food ' . $arguments, 'Food not found:');
        }
    },
    'Prevents deletion of foods referenced by daily entries' => static function (TestContext $t): void {
        $t->users->save(123, 'Maria');
        $user = $t->users->findByTelegramId(123);
        $daily = new App\Database\Repositories\DailyRepository($t->pdo);
        $entries = new App\Database\Repositories\DailyFoodRepository($t->pdo);
        $dailyId = $daily->create(['name' => 'Today', 'user_id' => $user['id']]);
        $t->command('/food Create Apple 52 fruits', 'Food created!');
        $food = $t->foods->findByName('Apple');
        $entries->create(['diet_id' => $dailyId, 'food_id' => $food['id'], 'quantity' => 100, 'consumed_at' => '2026-11-12 12:00:00']);
        $t->command('/food Delete Apple', 'used by daily food entries');
        $t->check($t->foods->findByName('Apple') !== null, 'Referenced food was deleted');
    },
    'Command without arguments shows usage' => static fn (TestContext $t) => $t->command('/food', 'Food commands:'),
    'Registers food and resolves the group by name' => static function (TestContext $t): void {
        $t->command('/food Create "Green apple" 52 FRUITS', ['Food created!', 'Green apple', 'Calories per 100 g: 52', 'Group: fruits']);
        $food = $t->foods->index()[0];
        $fruit = $t->groups->findByName('fruits');
        $t->check($food['name'] === 'Green apple' && (int) $food['energy'] === 52 && (int) $food['food_group_id'] === (int) $fruit['id'], 'Incorrect food data or foreign key');
    },
    'Accepts zero calories, spaces, and apostrophes' => static function (TestContext $t): void {
        $t->groups->create(['name' => "Chef's choice"]);
        $t->command('/food Create "Chef\'s special" 0 "Chef\'s choice"', ['Food created!', 'Calories per 100 g: 0', "Group: Chef's choice"]);
        $t->check(count($t->foods->index()) === 1, 'Food was not saved');
    },
    'Rejects a missing group or an ID instead of a name' => static function (TestContext $t): void {
        $t->command('/food Create Banana 89 missing', 'Group not found:');
        $t->command('/food Create Banana 89 ' . $t->groups->findByName('fruits')['id'], 'Group not found:');
        $t->check($t->foods->index() === [], 'Invalid food was saved');
    },
];
require_once dirname(__DIR__, 2) . '/app/database/repositories/DailyRepository.php';
require_once dirname(__DIR__, 2) . '/app/database/repositories/DailyFoodRepository.php';
foreach (['/food Index extra', '/food Read', '/food Read Apple extra', '/food Delete', '/food Update Apple', '/food Update Apple New 52', '/food Unknown', '/food Apple 52 fruits'] as $input) {
    $tests['Rejects invalid operation arguments: ' . $input] = static fn (TestContext $t) => $t->command($input, 'Food commands:');
}
foreach (['/food Create Banana -1 fruits', '/food Create Banana 2.5 fruits', '/food Create Banana abc fruits', '/food Create "" 52 fruits', '/food Create Banana 52 ""', '/food Create Banana 52', '/food Create Banana 52 fruits extra', '/food Create "broken 52 fruits', '/food Create Banana 99999999999999999999999999 fruits'] as $input) {
    $tests['Rejects invalid input: ' . $input] = static function (TestContext $t) use ($input): void {
        $t->command($input, 'Food commands:');
        $t->check($t->foods->index() === [], 'Invalid input inserted a food');
    };
}
return $tests;
