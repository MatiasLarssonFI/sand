<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use PDO;

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query('SELECT id, name, email FROM users ORDER BY name');

        return array_map(
            fn (array $row): User => new User((int) $row['id'], $row['name'], $row['email']),
            $statement->fetchAll()
        );
    }

    public function findById(int $userId): ?User
    {
        $statement = $this->pdo->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();

        return $row === false ? null : new User((int) $row['id'], $row['name'], $row['email']);
    }
}
