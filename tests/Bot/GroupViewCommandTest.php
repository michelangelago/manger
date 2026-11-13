<?php

declare(strict_types=1);

use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Support\TestContext;

return [
    'Lists foods under the correct groups with minimal calorie labels' => static function (TestContext $t): void {
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $t->command('/food -C Yogurt 60 dairy', 'Food created!');
        $reply = $t->command('/gview', ["Foods by group · kcal/100 g", "dairy\nYogurt · 60", "fruits\nApple · 52", 'vegetables · empty']);
        foreach (['ID:', 'Group:', '/gview', 'Consumed at:'] as $detail) {
            $t->check(!str_contains($reply, $detail), 'View includes unnecessary detail');
        }
    },
    'Filters a group by name ignoring case and supports spaces' => static function (TestContext $t): void {
        $t->groups->create(['name' => 'Whole grains']);
        $t->command('/food -C "Brown rice" 123 "Whole grains"', 'Food created!');
        $t->command('/food -C Apple 52 fruits', 'Food created!');
        $reply = $t->command('/gview "WHOLE GRAINS"', ["Whole grains\nBrown rice · 123", 'kcal/100 g']);
        $t->check(!str_contains($reply, 'Apple') && !str_contains($reply, 'fruits'), 'Filtered view leaked another group');
    },
    'Shows empty and missing groups clearly' => static function (TestContext $t): void {
        $t->command('/gview fruits', 'fruits · empty');
        $t->command('/gview missing', 'Food group not found: missing');
        $t->pdo->exec('DELETE FROM food_groups');
        $t->command('/gview', 'No food groups registered.');
    },
    'Rejects malformed group arguments' => static function (TestContext $t): void {
        foreach (['/gview ""', '/gview "broken', '/gview fruits dairy'] as $input) {
            $t->command($input, '/gview ["group name"]');
        }
    },
    'Splits large catalogs into short messages without dropping foods' => static function (TestContext $t): void {
        $group = $t->groups->findByName('fruits');
        for ($i = 0; $i < 150; $i++) {
            $t->foods->create(['name' => sprintf('Food %03d ', $i) . str_repeat('x', 60), 'energy' => 100, 'food_group_id' => $group['id']]);
        }
        $t->bot->hearText('/gview fruits')->reply();
        $history = $t->bot->getRequestHistory();
        $t->check(count($history) > 1, 'Large view was not split');
        $all = '';
        foreach ($history as $item) {
            $data = FakeNutgram::getActualData($item['request']);
            $text = $data['text'];
            $t->check(strlen($text) <= 3500 && str_contains($text, "fruits\n"), 'Page is too long or missing group heading');
            $all .= $text;
        }
        for ($i = 0; $i < 150; $i++) {
            $t->check(substr_count($all, sprintf('Food %03d ', $i)) === 1, 'Food was duplicated or omitted');
        }
    },
];
