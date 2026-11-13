<?php

declare(strict_types=1);

namespace App\Bot\Support;

use DateTimeImmutable;

final class CommandInput
{
    public static function operation(string $flag): ?string
    {
        return ['-i' => 'index', '-c' => 'create', '-r' => 'read', '-u' => 'update', '-d' => 'delete'][strtolower($flag)] ?? null;
    }

    public static function tokens(string $arguments): ?array
    {
        if (!preg_match('/^\s*(?:"[^"]*"|[^\s"]+)(?:\s+(?:"[^"]*"|[^\s"]+))*\s*$/', $arguments)) {
            return null;
        }
        preg_match_all('/"[^"]*"|[^\s"]+/', $arguments, $matches);
        $tokens = array_map(static fn (string $token): string => trim(trim($token, '"')), $matches[0]);
        return in_array('', $tokens, true) ? null : $tokens;
    }

    public static function positiveInteger(string $value): ?int
    {
        if (!preg_match('/^[0-9]+$/', $value)) {
            return null;
        }
        $number = filter_var(ltrim($value, '0') ?: '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $number === false ? null : $number;
    }

    public static function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
