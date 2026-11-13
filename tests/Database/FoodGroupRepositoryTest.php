<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Migrations create the six default groups' => static function (TestContext $t): void {
        $t->check(array_column($t->groups->index(), 'name') === ['fruits', 'vegetables', 'grains', 'protein', 'dairy', 'fats'], 'Incorrect default groups');
    },
    'Seeding can be repeated without duplicate groups' => static function (TestContext $t): void {
        $seed = require dirname(__DIR__, 2) . '/app/database/migrations/003_seed_food_groups.php';
        $seed($t->pdo);
        $t->check(count($t->groups->index()) === 6, 'Seeding duplicated groups');
    },
    'Name lookup ignores case and returns null when missing' => static function (TestContext $t): void {
        $t->check($t->groups->findByName('FRUITS') === $t->groups->findByName('fruits'), 'Name lookup is case-sensitive');
        $t->check($t->groups->findByName('missing') === null, 'Found a nonexistent group');
    },
];
