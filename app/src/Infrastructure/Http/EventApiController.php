<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Calendar\CalendarQueryService;
use App\Application\Event\EventService;
use App\Domain\Shared\AuthorizationException;
use App\Infrastructure\Security\SessionCsrfTokenManager;
use App\Infrastructure\Security\SessionCurrentUserProvider;

final class EventApiController
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly CalendarQueryService $calendarQueryService,
        private readonly SessionCurrentUserProvider $currentUserProvider,
        private readonly SessionCsrfTokenManager $csrfTokenManager,
    ) {
    }

    public function detail(Request $request, string $eventId): Response
    {
        $actorId = $this->requireCurrentUserId();
        $event = $this->eventService->detail($actorId, (int) $eventId);
        $state = $this->calendarQueryService->dashboard(
            $actorId,
            $event->calendarId(),
            'day',
            $event->timeRangeUtc()->start()->format('Y-m-d'),
            2
        );

        foreach ($state['events'] as $mappedEvent) {
            if ((int) $mappedEvent['id'] === $event->id()) {
                return Response::json(['event' => $mappedEvent]);
            }
        }

        return Response::json(['event' => null], 404);
    }

    public function create(Request $request): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $event = $this->eventService->create($actorId, $request->all());

        return Response::json(['id' => $event->id()], 201);
    }

    public function update(Request $request, string $eventId): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $event = $this->eventService->update($actorId, (int) $eventId, $request->all());

        return Response::json(['id' => $event->id()]);
    }

    public function delete(Request $request, string $eventId): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $actorId = $this->requireCurrentUserId();
        $this->eventService->delete($actorId, (int) $eventId);

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
