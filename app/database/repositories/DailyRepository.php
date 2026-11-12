<?php

declare(strict_types=1);

namespace App\Database\Repositories;

require_once __DIR__ . '/Repository.php';

final class DailyRepository extends Repository
{
    protected const TABLE = 'daily';
    protected const COLUMNS = ['name', 'user_id'];
}
