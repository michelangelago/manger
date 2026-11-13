<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use DateTimeImmutable;
use PDO;
use Throwable;

require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/DailyRepository.php';

final class DailyFoodRepository extends Repository
{
    protected const TABLE = 'daily_foods';
    protected const COLUMNS = ['diet_id', 'food_id', 'quantity', 'consumed_at'];

    public function indexForUser(int $userId, ?int $dailyId = null): array
    {
        $sql = 'SELECT df.*, f.name AS food_name, f.energy, d.date AS daily_date '
            . 'FROM daily_foods df JOIN daily d ON d.id = df.diet_id JOIN foods f ON f.id = df.food_id '
            . 'WHERE d.user_id = :user_id';
        $parameters = ['user_id' => $userId];
        if ($dailyId !== null) {
            $sql .= ' AND d.id = :daily_id';
            $parameters['daily_id'] = $dailyId;
        }
        $statement = $this->pdo->prepare($sql . ' ORDER BY df.consumed_at DESC, df.id DESC');
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readForUser(int $id, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT df.*, f.name AS food_name, f.energy, d.date AS daily_date '
            . 'FROM daily_foods df JOIN daily d ON d.id = df.diet_id JOIN foods f ON f.id = df.food_id '
            . 'WHERE df.id = :id AND d.user_id = :user_id'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        $entry = $statement->fetch(PDO::FETCH_ASSOC);
        return $entry === false ? null : $entry;
    }

    public function registerConsumption(int $userId, int $foodId, int $quantity, DateTimeImmutable $now): int
    {
        $this->pdo->beginTransaction();
        try {
            $daily = (new DailyRepository($this->pdo))->getOrCreateForDate($userId, $now->format('Y-m-d'));
            $id = $this->create([
                'diet_id' => $daily['id'],
                'food_id' => $foodId,
                'quantity' => $quantity,
                'consumed_at' => $now->format('Y-m-d H:i:sP'),
            ]);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function updateForUser(int $id, int $userId, int $foodId, int $quantity): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE daily_foods SET food_id = :food_id, quantity = :quantity WHERE id = :id '
            . 'AND diet_id IN (SELECT id FROM daily WHERE user_id = :user_id)'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId, 'food_id' => $foodId, 'quantity' => $quantity]);
        return $statement->rowCount() > 0;
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM daily_foods WHERE id = :id AND diet_id IN (SELECT id FROM daily WHERE user_id = :user_id)'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        return $statement->rowCount() > 0;
    }
}
