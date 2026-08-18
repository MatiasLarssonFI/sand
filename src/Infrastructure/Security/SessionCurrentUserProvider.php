<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\UserRepositoryInterface;

final class SessionCurrentUserProvider
{
    private const SESSION_KEY = '_current_user_id';

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function currentUserId(): ?int
    {
        $userId = $_SESSION[self::SESSION_KEY] ?? null;

        if (is_int($userId) && $this->userRepository->findById($userId) !== null) {
            return $userId;
        }

        $users = $this->userRepository->findAll();

        if ($users === []) {
            return null;
        }

        $_SESSION[self::SESSION_KEY] = $users[0]->id();

        return $users[0]->id();
    }

    public function switchTo(int $userId): void
    {
        if ($this->userRepository->findById($userId) === null) {
            return;
        }

        $_SESSION[self::SESSION_KEY] = $userId;
    }
}
