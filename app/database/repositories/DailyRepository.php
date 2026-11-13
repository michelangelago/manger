<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class DailyRepository extends Repository
{
    protected const TABLE = 'daily';
    protected const COLUMNS = ['name', 'user_id', 'date'];

    public function indexForUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM daily WHERE user_id = :user_id ORDER BY date DESC, id DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDate(int $userId, string $date): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM daily WHERE user_id = :user_id AND date = :date');
        $statement->execute(['user_id' => $userId, 'date' => $date]);
        $daily = $statement->fetch(PDO::FETCH_ASSOC);
        return $daily === false ? null : $daily;
    }

    public function getOrCreateForDate(int $userId, string $date): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO daily (name, user_id, date) VALUES (:date, :user_id, :date) '
            . 'ON CONFLICT (user_id, date) DO NOTHING'
        );
        $statement->execute(['user_id' => $userId, 'date' => $date]);
        return $this->findByDate($userId, $date);
    }
}
