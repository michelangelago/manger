<?php

declare(strict_types=1);

use Tests\Support\TestContext;

return [
    'Hello sends a personalized greeting' => static fn (TestContext $t) => $t->command('/hello Maria', 'Hello, Maria!'),
];
