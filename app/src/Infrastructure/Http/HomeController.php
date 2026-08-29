<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Calendar\CalendarQueryService;
use App\Infrastructure\Security\SessionCsrfTokenManager;
use App\Infrastructure\Security\SessionCurrentUserProvider;

final class HomeController
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly CalendarQueryService $calendarQueryService,
        private readonly SessionCurrentUserProvider $currentUserProvider,
        private readonly SessionCsrfTokenManager $csrfTokenManager,
        private readonly array $appConfig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $currentUserId = $this->currentUserProvider->currentUserId();
        $state = $currentUserId === null
            ? [
                'calendars' => [],
                'selectedCalendar' => null,
                'view' => null,
                'events' => [],
                'members' => [],
                'users' => [],
                'permissions' => ['view' => false, 'edit' => false, 'manage' => false],
            ]
            : $this->calendarQueryService->dashboard(
                $currentUserId,
                ($calendarId = $request->query('calendar_id')) !== null ? (int) $calendarId : null,
                is_string($request->query('view')) ? $request->query('view') : null,
                is_string($request->query('date')) ? $request->query('date') : null,
                ($weeks = $request->query('weeks')) !== null ? (int) $weeks : null
            );

        return Response::html($this->renderer->render('home.php', [
            'appName' => $this->appConfig['name'],
            'csrfToken' => $this->csrfTokenManager->token(),
            'currentUserId' => $currentUserId,
            'initialState' => $state,
            'defaultWeeks' => $this->appConfig['default_weeks'],
        ]));
    }
}
