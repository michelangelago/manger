<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class UserRepository extends Repository
{
    protected const TABLE = 'users';
    protected const COLUMNS = ['telegram_id', 'name', 'username'];

    public function save(int $telegramId, string $name): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO users (telegram_id, name)
            VALUES (:telegram_id, :name)
            ON CONFLICT (telegram_id) DO UPDATE SET name = excluded.name
            SQL);

        $statement->execute(['telegram_id' => $telegramId, 'name' => $name]);
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, telegram_id, name, created_at FROM users WHERE telegram_id = :telegram_id'
        );
        $statement->execute(['telegram_id' => $telegramId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }
}
