<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Help shows all commands and operations' => static fn (TestContext $t) => $t->command('/help', ['/start', '/hello', '/help', '/food Index', '/food Create', '/food Read', '/food Update', '/food Delete', 'calories per 100 grams', '/fgroup Index', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']),
];
