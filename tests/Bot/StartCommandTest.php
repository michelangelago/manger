<?php

declare(strict_types=1);

use SergiX44\Nutgram\Telegram\Types\User\User;
use Tests\Support\TestContext;

return [
    'Start registers a user and shows saved details' => static function (TestContext $t): void {
        $t->bot->setCommonUser(User::make(123456789, false, 'Maria', 'Silva'));
        $t->command('/start', ['You are registered!', 'Maria Silva', 'Telegram ID: 123456789', 'Registered at (UTC):']);
        $user = $t->users->findByTelegramId(123456789);
        $t->check($user !== null && $user['name'] === 'Maria Silva', 'User was not saved');
    },
    'Repeated start updates the name and welcomes the user without duplicates' => static function (TestContext $t): void {
        $t->users->save(123456789, 'Maria Silva');
        $original = $t->users->findByTelegramId(123456789);
        $t->bot->setCommonUser(User::make(123456789, false, 'Maria'));
        $t->command('/start', ['Welcome back! You are already registered.', 'Name: Maria']);
        $updated = $t->users->findByTelegramId(123456789);
        $t->check(count($t->users->index()) === 1 && $updated['id'] === $original['id'] && $updated['name'] === 'Maria' && $updated['created_at'] === $original['created_at'], 'Incorrect repeat registration');
    },
];
