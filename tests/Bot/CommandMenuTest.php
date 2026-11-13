<?php

declare(strict_types=1);

use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Support\TestContext;

return [
    'Registers all available commands with Telegram autocomplete' => static function (TestContext $t): void {
        $sync = require dirname(__DIR__, 2) . '/app/bot/menu.php';
        $bot = FakeNutgram::instance();
        $sync($bot);
        $history = $bot->getRequestHistory();
        $t->check(count($history) === 1, 'Expected one menu synchronization request');
        $request = $history[0]['request'];
        $t->check(basename($request->getUri()->getPath()) === 'setMyCommands', 'Wrong Telegram method');
        $data = FakeNutgram::getActualData($request);
        $commands = is_string($data['commands']) ? json_decode($data['commands'], true, flags: JSON_THROW_ON_ERROR) : $data['commands'];
        $t->check(array_column($commands, 'command') === ['start', 'help', 'fgroup', 'food', 'daily', 'dfoods', 'dview'], 'Menu contains missing or obsolete commands');
        foreach ($commands as $command) {
            $t->check(strlen($command['description']) >= 1 && strlen($command['description']) <= 256, 'Invalid menu description');
        }
        $t->check(str_contains($commands[3]['description'], '-C') && str_contains($commands[5]['description'], '-D'), 'Menu descriptions omit operation flags');
    },
];
