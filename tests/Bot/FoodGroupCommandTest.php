<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Command without arguments shows usage' => static fn (TestContext $t) => $t->command('/fgroup', 'Food group commands:'),
    'Index shows groups and operations' => static fn (TestContext $t) => $t->command('/fgroup -I', ['fruits', '/fgroup -C', '/fgroup -R', '/fgroup -U', '/fgroup -D']),
    'Empty index still shows available operations' => static function (TestContext $t): void {
        $t->pdo->exec('DELETE FROM food_groups');
        $t->command('/fgroup -I', ['No food groups registered.', '/fgroup -C', '/fgroup -R', '/fgroup -U', '/fgroup -D']);
    },
    'Creates, reads, renames, and deletes a group with spaces' => static function (TestContext $t): void {
        $t->command('/fgroup -C "Whole grains"', 'Food group created.');
        $t->command('/fgroup -R "WHOLE GRAINS"', 'Whole grains');
        $t->command('/fgroup -U "Whole grains" "Cereal grains"', 'Food group updated.');
        $t->check($t->groups->findByName('Whole grains') === null, 'Old name is still available');
        $t->command('/fgroup -D "Cereal grains"', 'Food group deleted:');
        $t->command('/fgroup -R "Cereal grains"', 'not found');
    },
    'Prevents creation and updates with duplicate names' => static function (TestContext $t): void {
        $t->command('/fgroup -C FRUITS', 'already exists');
        $t->command('/fgroup -U grains fruits', 'already exists');
        $t->check(count($t->groups->index()) === 6, 'Created a duplicate group');
        $t->check($t->groups->findByName('grains') !== null, 'Invalid update changed the group');
    },
    'Prevents deletion of a group referenced by food' => static function (TestContext $t): void {
        $fruit = $t->groups->findByName('fruits');
        $t->foods->create(['name' => 'Apple', 'energy' => 52, 'food_group_id' => $fruit['id']]);
        $t->command('/fgroup -D fruits', 'used by foods');
        $t->check($t->groups->findByName('fruits') !== null, 'Referenced group was deleted');
    },
    'Accepts apostrophes in names' => static function (TestContext $t): void {
        $t->command('/fgroup -C "Chef\'s choice"', 'Food group created.');
        $t->check($t->groups->findByName("Chef's choice") !== null, 'Name was not saved');
    },
];
foreach (['/fgroup -C ""', '/fgroup -U fruits', '/fgroup -I extra', '/fgroup Unknown', '/fgroup -C "broken'] as $input) {
    $tests['Rejects invalid input: ' . $input] = static function (TestContext $t) use ($input): void {
        $t->command($input, 'Food group commands:');
        $t->check(count($t->groups->index()) === 6, 'Invalid input changed groups');
    };
}
return $tests;
