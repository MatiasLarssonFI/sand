<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Calendar\CalendarQueryService;
use App\Domain\Shared\ValidationException;
use App\Infrastructure\Security\SessionCsrfTokenManager;
use App\Infrastructure\Security\SessionCurrentUserProvider;

final class CalendarApiController
{
    public function __construct(
        private readonly CalendarQueryService $calendarQueryService,
        private readonly SessionCurrentUserProvider $currentUserProvider,
        private readonly SessionCsrfTokenManager $csrfTokenManager,
    ) {
    }

    public function show(Request $request): Response
    {
        $currentUserId = $this->currentUserProvider->currentUserId();

        if ($currentUserId === null) {
            throw new ValidationException('No active user is available.');
        }

        return Response::json($this->calendarQueryService->dashboard(
            $currentUserId,
            ($calendarId = $request->query('calendar_id')) !== null ? (int) $calendarId : null,
            is_string($request->query('view')) ? $request->query('view') : null,
            is_string($request->query('date')) ? $request->query('date') : null,
            ($weeks = $request->query('weeks')) !== null ? (int) $weeks : null
        ));
    }

    public function switchUser(Request $request): Response
    {
        $this->csrfTokenManager->assertValid($request->header('X-CSRF-Token'));
        $userId = filter_var($request->input('userId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userId === false) {
            throw new ValidationException('A valid user is required.');
        }

        $this->currentUserProvider->switchTo($userId);

        return Response::json(['ok' => true]);
    }
}
