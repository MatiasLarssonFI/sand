<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\ValidationException;

final class CalendarMember
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    private const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_EDITOR,
        self::ROLE_VIEWER,
    ];

    public function __construct(
        private readonly int $id,
        private readonly int $calendarId,
        private readonly int $userId,
        private readonly string $role,
    ) {
        if (!in_array($role, self::ROLES, true)) {
            throw new ValidationException('Invalid member role.');
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function calendarId(): int
    {
        return $this->calendarId;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function role(): string
    {
        return $this->role;
    }

    public static function roles(): array
    {
        return self::ROLES;
    }

    public function canEdit(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_EDITOR], true);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }
}
