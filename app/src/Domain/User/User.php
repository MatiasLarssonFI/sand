<?php

declare(strict_types=1);

namespace App\Domain\User;

final class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $email,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }
}
