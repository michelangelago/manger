<?php

declare(strict_types=1);

namespace App\Bot\Commands;

use App\Database\Repositories\UserRepository;
use SergiX44\Nutgram\Nutgram;

require_once dirname(__DIR__, 2) . '/database/repositories/UserRepository.php';

final class StartCommand
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(Nutgram $bot): void
    {
        $user = $bot->user();

        if ($user === null) {
            return;
        }

        $name = trim($user->first_name . ' ' . ($user->last_name ?? ''));
        $alreadyRegistered = $this->users->findByTelegramId($user->id) !== null;
        $this->users->save($user->id, $name);
        $registeredUser = $this->users->findByTelegramId($user->id);
        $message = $alreadyRegistered
            ? "Welcome back! You are already registered."
            : "You are registered!";

        $bot->sendMessage(
            $message . "\n\n"
            . "ID: {$registeredUser['id']}\n"
            . "Telegram ID: {$registeredUser['telegram_id']}\n"
            . "Name: {$registeredUser['name']}\n"
            . "Registered at (UTC): {$registeredUser['created_at']}"
        );
    }
}
