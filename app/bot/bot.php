<?php

declare(strict_types=1);

$bot = require __DIR__ . '/bootstrap.php';
$syncMenu = require __DIR__ . '/menu.php';
$syncMenu($bot);
$bot->run();
