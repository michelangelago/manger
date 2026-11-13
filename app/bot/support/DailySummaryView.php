<?php

declare(strict_types=1);

namespace App\Bot\Support;

final class DailySummaryView
{
    public static function render(string $period, array $foods): string
    {
        $total = self::number(array_sum(array_column($foods, 'calories')));
        $header = "Total: {$total} kcal\n{$period}\n\n";
        if ($foods === []) {
            return $header . 'No foods logged.';
        }
        $lines = [];
        foreach (array_slice($foods, 0, 15) as $food) {
            $characters = preg_split('//u', $food['food_name'], -1, PREG_SPLIT_NO_EMPTY);
            $name = count($characters) > 80 ? implode('', array_slice($characters, 0, 79)) . '…' : $food['food_name'];
            $lines[] = $name . ' · ' . self::number((float) $food['grams']) . ' g · '
                . self::number((float) $food['calories']) . ' kcal';
        }
        if (count($foods) > 15) {
            $lines[] = '+' . (count($foods) - 15) . ' more foods (included in total)';
        }
        return $header . implode("\n", $lines);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
