<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use PDO;

require_once __DIR__ . '/Repository.php';

final class GoalRepository extends Repository
{
    protected const TABLE = 'calorie_goals';
    protected const COLUMNS = ['user_id', 'calories'];

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM calorie_goals WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $goal = $statement->fetch(PDO::FETCH_ASSOC);
        return $goal === false ? null : $goal;
    }

    public function createForUser(int $userId, int $calories): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO calorie_goals (user_id, calories) VALUES (:user_id, :calories) ON CONFLICT (user_id) DO NOTHING'
        );
        $statement->execute(['user_id' => $userId, 'calories' => $calories]);
        return $statement->rowCount() > 0;
    }

    public function updateForUser(int $userId, int $calories): bool
    {
        $statement = $this->pdo->prepare('UPDATE calorie_goals SET calories = :calories WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId, 'calories' => $calories]);
        return $statement->rowCount() > 0;
    }

    public function deleteForUser(int $userId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM calorie_goals WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return $statement->rowCount() > 0;
    }
}
