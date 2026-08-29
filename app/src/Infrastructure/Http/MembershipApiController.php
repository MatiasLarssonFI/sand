<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Calendar\MembershipService;
use App\Domain\Shared\AuthorizationException;
use App\Domain\Shared\ValidationException;
use App\Infrastructure\Security\SessionCsrfTokenManager;
use App\Infrastructure\Security\SessionCurrentUserProvider;

final class MembershipApiController
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly SessionCurrentUserProvider $currentUserProvider,
        private readonly SessionCsrfTokenManager $csrfTokenManager,
    ) {
    }

    public function create(Request $request): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $calendarId = filter_var($request->input('calendarId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = filter_var($request->input('userId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $role = trim((string) $request->input('role'));

        if ($calendarId === false || $userId === false) {
            throw new ValidationException('A valid calendar and user are required.');
        }

        $member = $this->membershipService->addMember($actorId, $calendarId, $userId, $role);

        return Response::json(['id' => $member->id()], 201);
    }

    public function update(Request $request, string $membershipId): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $this->membershipService->updateMemberRole($actorId, (int) $membershipId, trim((string) $request->input('role')));

        return Response::json(['ok' => true]);
    }

    public function delete(Request $request, string $membershipId): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $this->membershipService->removeMember($actorId, (int) $membershipId);

        return Response::json(['ok' => true]);
    }

    private function requireCurrentUserId(): int
    {
        $userId = $this->currentUserProvider->currentUserId();

        if ($userId === null) {
            throw new AuthorizationException('No active user is available.');
        }

        return $userId;
    }
}
