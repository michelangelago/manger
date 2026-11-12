<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use InvalidArgumentException;
use PDO;

abstract class Repository
{
    protected const TABLE = '';
    protected const COLUMNS = [];

    public function __construct(protected PDO $pdo)
    {
    }

    public function index(): array
    {
        return $this->pdo->query('SELECT * FROM ' . static::TABLE . ' ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validateColumns($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $statement = $this->pdo->prepare(
            'INSERT INTO ' . static::TABLE . " ({$columns}) VALUES ({$placeholders})"
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function read(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM ' . static::TABLE . ' WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function update(int $id, array $data): bool
    {
        $this->validateColumns($data);
        $assignments = implode(', ', array_map(
            static fn (string $column): string => "{$column} = :{$column}",
            array_keys($data)
        ));
        $statement = $this->pdo->prepare(
            'UPDATE ' . static::TABLE . " SET {$assignments} WHERE id = :_id"
        );
        $statement->execute([...$data, '_id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM ' . static::TABLE . ' WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function validateColumns(array $data): void
    {
        if ($data === [] || array_diff(array_keys($data), static::COLUMNS) !== []) {
            throw new InvalidArgumentException('Provide a non-empty array containing only writable columns.');
        }
    }
}
