<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

interface CalendarRepositoryInterface
{
    /** @return Calendar[] */
    public function findAccessibleByUserId(int $userId): array;

    public function findById(int $calendarId): ?Calendar;

    /** @return CalendarMember[] */
    public function findMemberships(int $calendarId): array;

    public function findMembership(int $calendarId, int $userId): ?CalendarMember;

    public function findMembershipById(int $membershipId): ?CalendarMember;

    public function createMembership(int $calendarId, int $userId, string $role): CalendarMember;

    public function updateMembershipRole(int $membershipId, string $role): void;

    public function deleteMembership(int $membershipId): void;

    public function countOwners(int $calendarId): int;
}
