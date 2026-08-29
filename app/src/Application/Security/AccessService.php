<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Domain\Calendar\CalendarMember;
use App\Domain\Calendar\CalendarRepositoryInterface;
use App\Domain\Shared\AuthorizationException;
use App\Domain\Shared\NotFoundException;

final class AccessService
{
    public function __construct(private readonly CalendarRepositoryInterface $calendarRepository)
    {
    }

    public function membershipFor(int $calendarId, int $userId): ?CalendarMember
    {
        return $this->calendarRepository->findMembership($calendarId, $userId);
    }

    public function assertCanView(int $calendarId, int $userId): CalendarMember
    {
        $membership = $this->membershipFor($calendarId, $userId);

        if ($membership === null) {
            throw new AuthorizationException('You do not have access to this calendar.');
        }

        return $membership;
    }

    public function assertCanEdit(int $calendarId, int $userId): CalendarMember
    {
        $membership = $this->assertCanView($calendarId, $userId);

        if (!$membership->canEdit()) {
            throw new AuthorizationException('You do not have permission to edit events in this calendar.');
        }

        return $membership;
    }

    public function assertCanManage(int $calendarId, int $userId): CalendarMember
    {
        $membership = $this->assertCanView($calendarId, $userId);

        if (!$membership->isOwner()) {
            throw new AuthorizationException('Only calendar owners can manage sharing.');
        }

        return $membership;
    }

    public function assertCalendarExists(int $calendarId): void
    {
        if ($this->calendarRepository->findById($calendarId) === null) {
            throw new NotFoundException('Calendar not found.');
        }
    }
}
