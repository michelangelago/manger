<?php

declare(strict_types=1);

namespace App\Bot\Support;

final class GroupFoodView
{
    public static function pages(array $rows): array
    {
        if ($rows === []) {
            return ['No food groups registered.'];
        }
        $header = "Foods by group · kcal/100 g\n";
        $page = $header;
        $pages = [];
        $previousGroup = null;
        foreach ($rows as $row) {
            $group = self::name($row['group_name']);
            $heading = "\n{$group}" . ($row['food_name'] === null ? ' · empty' : '') . "\n";
            $food = $row['food_name'] === null ? '' : self::name($row['food_name']) . " · {$row['energy']}\n";
            $line = ($previousGroup !== $row['group_id'] ? $heading : '') . $food;
            if (strlen($page . $line) > 3500) {
                $pages[] = rtrim($page);
                $page = $header;
                $line = $heading . $food;
            }
            $page .= $line;
            $previousGroup = $row['group_id'];
        }
        $pages[] = rtrim($page);
        return $pages;
    }

    private static function name(string $name): string
    {
        // Keep names on a single compact line, including unusual catalog input.
        $name = preg_replace('/\s+/u', ' ', trim($name));
        $characters = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
        return count($characters) > 80 ? implode('', array_slice($characters, 0, 79)) . '…' : $name;
    }
}
