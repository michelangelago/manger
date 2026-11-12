<?php

declare(strict_types=1);

namespace App\Database\Repositories;

require_once __DIR__ . '/Repository.php';

final class FoodRepository extends Repository
{
    protected const TABLE = 'foods';
    protected const COLUMNS = ['name', 'energy', 'food_group_id'];
}
