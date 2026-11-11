<?php

declare(strict_types=1);

namespace App\Database\Repositories;

require_once __DIR__ . '/Repository.php';

final class DailyFoodRepository extends Repository
{
    protected const TABLE = 'daily_foods';
    protected const COLUMNS = ['diet_id', 'food_id', 'quantity', 'consumed_at'];
}
