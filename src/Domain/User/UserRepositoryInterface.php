<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepositoryInterface
{
    /** @return User[] */
    public function findAll(): array;

    public function findById(int $userId): ?User;
}
