<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Help shows all commands and operations' => static fn (TestContext $t) => $t->command('/help', ['/start', '/help', '/food -I', '/food -C', '/food -R', '/food -U', '/food -D', 'calories per 100 grams', '/fgroup -I', '/fgroup -C', '/fgroup -R', '/fgroup -U', '/fgroup -D', '/daily -I', '/daily -R', '/dfoods -I', '/dfoods -C', '/dfoods -R', '/dfoods -U', '/dfoods -D']),
];
