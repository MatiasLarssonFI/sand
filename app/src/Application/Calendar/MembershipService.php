<?php

declare(strict_types=1);

namespace App\Application\Calendar;

use App\Application\Security\AccessService;
use App\Domain\Calendar\CalendarMember;
use App\Domain\Calendar\CalendarRepositoryInterface;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Shared\ValidationException;
use App\Domain\User\UserRepositoryInterface;

final class MembershipService
{
    public function __construct(
        private readonly CalendarRepositoryInterface $calendarRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccessService $accessService,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function addMember(int $actorId, int $calendarId, int $userId, string $role): CalendarMember
    {
        $this->accessService->assertCanManage($calendarId, $actorId);

        if (!in_array($role, CalendarMember::roles(), true)) {
            throw new ValidationException('Invalid member role.');
        }

        if ($this->userRepository->findById($userId) === null) {
            throw new NotFoundException('User not found.');
        }

        if ($this->calendarRepository->findMembership($calendarId, $userId) !== null) {
            throw new ValidationException('User is already a member of this calendar.');
        }

        return $this->transactionManager->run(
            fn (): CalendarMember => $this->calendarRepository->createMembership($calendarId, $userId, $role)
        );
    }

    public function updateMemberRole(int $actorId, int $membershipId, string $role): void
    {
        if (!in_array($role, CalendarMember::roles(), true)) {
            throw new ValidationException('Invalid member role.');
        }

        $membership = $this->calendarRepository->findMembershipById($membershipId);

        if ($membership === null) {
            throw new NotFoundException('Member not found.');
        }

        $this->accessService->assertCanManage($membership->calendarId(), $actorId);

        if ($membership->isOwner() && $role !== CalendarMember::ROLE_OWNER && $this->calendarRepository->countOwners($membership->calendarId()) === 1) {
            throw new ValidationException('At least one owner must remain on the calendar.');
        }

        $this->transactionManager->run(function () use ($membershipId, $role): void {
            $this->calendarRepository->updateMembershipRole($membershipId, $role);
        });
    }

    public function removeMember(int $actorId, int $membershipId): void
    {
        $membership = $this->calendarRepository->findMembershipById($membershipId);

        if ($membership === null) {
            throw new NotFoundException('Member not found.');
        }

        $this->accessService->assertCanManage($membership->calendarId(), $actorId);

        if ($membership->isOwner() && $this->calendarRepository->countOwners($membership->calendarId()) === 1) {
            throw new ValidationException('At least one owner must remain on the calendar.');
        }

        $this->transactionManager->run(function () use ($membershipId): void {
            $this->calendarRepository->deleteMembership($membershipId);
        });
    }
}
