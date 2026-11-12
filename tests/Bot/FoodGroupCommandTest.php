<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [
    'Command without arguments shows usage' => static fn (TestContext $t) => $t->command('/fgroup', 'Food group commands:'),
    'Index shows groups and operations' => static fn (TestContext $t) => $t->command('/fgroup Index', ['fruits', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']),
    'Empty index still shows available operations' => static function (TestContext $t): void {
        $t->pdo->exec('DELETE FROM food_groups');
        $t->command('/fgroup Index', ['No food groups registered.', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']);
    },
    'Creates, reads, renames, and deletes a group with spaces' => static function (TestContext $t): void {
        $t->command('/fgroup Create "Whole grains"', 'Food group created.');
        $t->command('/fgroup Read "WHOLE GRAINS"', 'Whole grains');
        $t->command('/fgroup Update "Whole grains" "Cereal grains"', 'Food group updated.');
        $t->check($t->groups->findByName('Whole grains') === null, 'Old name is still available');
        $t->command('/fgroup Delete "Cereal grains"', 'Food group deleted:');
        $t->command('/fgroup Read "Cereal grains"', 'not found');
    },
    'Prevents creation and updates with duplicate names' => static function (TestContext $t): void {
        $t->command('/fgroup Create FRUITS', 'already exists');
        $t->command('/fgroup Update grains fruits', 'already exists');
        $t->check(count($t->groups->index()) === 6, 'Created a duplicate group');
        $t->check($t->groups->findByName('grains') !== null, 'Invalid update changed the group');
    },
    'Prevents deletion of a group referenced by food' => static function (TestContext $t): void {
        $fruit = $t->groups->findByName('fruits');
        $t->foods->create(['name' => 'Apple', 'energy' => 52, 'food_group_id' => $fruit['id']]);
        $t->command('/fgroup Delete fruits', 'used by foods');
        $t->check($t->groups->findByName('fruits') !== null, 'Referenced group was deleted');
    },
    'Accepts apostrophes in names' => static function (TestContext $t): void {
        $t->command('/fgroup Create "Chef\'s choice"', 'Food group created.');
        $t->check($t->groups->findByName("Chef's choice") !== null, 'Name was not saved');
    },
];
foreach (['/fgroup Create ""', '/fgroup Update fruits', '/fgroup Index extra', '/fgroup Unknown', '/fgroup Create "broken'] as $input) {
    $tests['Rejects invalid input: ' . $input] = static function (TestContext $t) use ($input): void {
        $t->command($input, 'Food group commands:');
        $t->check(count($t->groups->index()) === 6, 'Invalid input changed groups');
    };
}
return $tests;
