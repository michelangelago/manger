<?php

declare(strict_types=1);

use Tests\Support\TestContext;

$tests = [];
foreach (['food' => 'Food commands:', 'fgroup' => 'Food group commands:', 'daily' => 'Daily logs:', 'dfoods' => 'Consumed foods:'] as $command => $usage) {
    foreach (['Index', '-X', '-CC'] as $invalid) {
        $tests[$command . ' rejects operation: ' . $invalid] = static function (TestContext $t) use ($command, $usage, $invalid): void {
            $t->actAs(123);
            $t->command('/' . $command . ' ' . $invalid, $usage);
        };
    }
}
$tests['Lowercase flags work across all command modules'] = static function (TestContext $t): void {
    $t->actAs(123);
    $t->command('/fgroup -c "Extra group"', 'Food group created.');
    $t->command('/fgroup -r "Extra group"', 'Name: Extra group');
    $t->command('/food -c Apple 52 fruits', 'Food created!');
    $t->command('/food -r Apple', 'Name: Apple');
    $t->command('/food -u Apple Apple 55 fruits', 'Food updated!');
    $t->command('/dfoods -c Apple 100', 'Food entry created!');
    $id = $t->entries->index()[0]['id'];
    $t->command('/dfoods -r ' . $id, 'Food: Apple');
    $t->command('/dfoods -u ' . $id . ' Apple 200', 'Food entry updated!');
    $t->command('/dfoods -i', 'Quantity: 200 g');
    $t->command('/daily -i', '/daily -R');
    $t->command('/daily -r', 'Total calories: 110.00 kcal');
    $t->command('/dfoods -d ' . $id, 'Food entry deleted:');
    $t->command('/food -d Apple', 'Food deleted:');
    $t->command('/fgroup -d "Extra group"', 'Food group deleted:');
};
return $tests;
