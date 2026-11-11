<?php

declare(strict_types=1);

namespace App\Database\Repositories;

require_once __DIR__ . '/Repository.php';

final class FoodGroupRepository extends Repository
{
    protected const TABLE = 'food_groups';
    protected const COLUMNS = ['name'];
}
