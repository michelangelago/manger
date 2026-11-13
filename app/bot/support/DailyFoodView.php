<?php

declare(strict_types=1);

namespace App\Bot\Support;

final class DailyFoodView
{
    public static function calories(array $entry): float
    {
        return (float) $entry['energy'] * (float) $entry['quantity'] / 100;
    }

    public static function describe(array $entry): string
    {
        $calories = number_format(self::calories($entry), 2, '.', '');
        return "Entry ID: {$entry['id']}\nFood: {$entry['food_name']}\n"
            . "Quantity: {$entry['quantity']} g\nCalories: {$calories} kcal\n"
            . "Consumed at: {$entry['consumed_at']}";
    }
}
