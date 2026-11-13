<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Database enforces one goal per user and positive whole calories' => static function (TestContext $t): void {
        $t->actAs(123);
        $userId = (int) $t->users->findByTelegramId(123)['id'];
        $t->check($t->goals->createForUser($userId, 2000), 'Goal creation failed');
        $t->check(!$t->goals->createForUser($userId, 1800), 'Duplicate goal was created');
        foreach ([['user_id' => $userId, 'calories' => 1800], ['user_id' => 999, 'calories' => 2000]] as $data) {
            try {
                $t->goals->create($data);
                throw new RuntimeException('Database accepted invalid goal');
            } catch (PDOException $exception) {
                $t->check(str_contains($exception->getMessage(), 'constraint failed'), 'Unexpected database error');
            }
        }
        $t->goals->deleteForUser($userId);
        foreach ([0, -1, 2.5] as $calories) {
            try {
                $t->goals->create(['user_id' => $userId, 'calories' => $calories]);
                throw new RuntimeException('Database accepted invalid calories');
            } catch (PDOException $exception) {
                $t->check(str_contains($exception->getMessage(), 'CHECK constraint failed'), 'Unexpected database error');
            }
        }
    },
];
