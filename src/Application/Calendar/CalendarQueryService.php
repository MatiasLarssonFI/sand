<?php

declare(strict_types=1);

namespace App\Application\Calendar;

use App\Application\Security\AccessService;
use App\Domain\Calendar\Calendar;
use App\Domain\Calendar\CalendarMember;
use App\Domain\Calendar\CalendarRepositoryInterface;
use App\Domain\Calendar\ViewPeriodInterface;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;

final class CalendarQueryService
{
    public function __construct(
        private readonly CalendarRepositoryInterface $calendarRepository,
        private readonly EventRepositoryInterface $eventRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccessService $accessService,
        private readonly CalendarViewFactory $viewFactory,
        private readonly array $appConfig,
    ) {
    }

    public function dashboard(int $currentUserId, ?int $requestedCalendarId, ?string $view, ?string $date, ?int $weeks): array
    {
        $calendars = $this->calendarRepository->findAccessibleByUserId($currentUserId);
        $users = $this->userRepository->findAll();

        if ($calendars === []) {
            return [
                'calendars' => [],
                'selectedCalendar' => null,
                'view' => null,
                'events' => [],
                'members' => [],
                'users' => $this->mapUsers($users),
                'permissions' => ['view' => false, 'edit' => false, 'manage' => false],
            ];
        }

        $selectedCalendar = $this->resolveCalendar($calendars, $requestedCalendarId);
        $membership = $this->accessService->assertCanView($selectedCalendar->id(), $currentUserId);

        $viewPeriod = $this->viewFactory->create(
            $view ?: (string) $this->appConfig['default_view'],
            $date ?: 'now',
            $selectedCalendar->timezone(),
            $weeks ?? (int) $this->appConfig['default_weeks'],
        );

        $events = $this->eventRepository->findByCalendarAndRange(
            $selectedCalendar->id(),
            $viewPeriod->range()->inTimezone(new DateTimeZone('UTC'))
        );

        return [
            'calendars' => array_map(fn (Calendar $calendar): array => [
                'id' => $calendar->id(),
                'name' => $calendar->name(),
                'timezone' => $calendar->timezone(),
            ], $calendars),
            'selectedCalendar' => [
                'id' => $selectedCalendar->id(),
                'name' => $selectedCalendar->name(),
                'timezone' => $selectedCalendar->timezone(),
            ],
            'view' => $this->mapView($viewPeriod),
            'events' => array_map(
                fn (Event $event): array => $this->mapEvent($event, $selectedCalendar, $membership),
                $events
            ),
            'members' => $this->mapMembers(
                $this->calendarRepository->findMemberships($selectedCalendar->id()),
                $users
            ),
            'users' => $this->mapUsers($users),
            'permissions' => [
                'view' => true,
                'edit' => $membership->canEdit(),
                'manage' => $membership->isOwner(),
            ],
        ];
    }

    private function resolveCalendar(array $calendars, ?int $requestedCalendarId): Calendar
    {
        foreach ($calendars as $calendar) {
            if ($requestedCalendarId !== null && $calendar->id() === $requestedCalendarId) {
                return $calendar;
            }
        }

        return $calendars[0];
    }

    private function mapEvent(Event $event, Calendar $calendar, CalendarMember $membership): array
    {
        $timezone = new DateTimeZone($calendar->timezone());
        $localRange = $event->timeRangeUtc()->inTimezone($timezone);

        return [
            'id' => $event->id(),
            'calendarId' => $event->calendarId(),
            'title' => $event->title(),
            'description' => $event->description(),
            'start' => $localRange->start()->format(DATE_ATOM),
            'end' => $localRange->end()->format(DATE_ATOM),
            'startInput' => $localRange->start()->format('Y-m-d\TH:i'),
            'endInput' => $localRange->end()->format('Y-m-d\TH:i'),
            'startDate' => $localRange->start()->format('Y-m-d'),
            'timeLabel' => sprintf('%s – %s', $localRange->start()->format('H:i'), $localRange->end()->format('H:i')),
            'editable' => $membership->canEdit(),
        ];
    }

    private function mapMembers(array $memberships, array $users): array
    {
        $usersById = [];

        foreach ($users as $user) {
            $usersById[$user->id()] = $user;
        }

        return array_map(static function (CalendarMember $membership) use ($usersById): array {
            $user = $usersById[$membership->userId()] ?? null;

            return [
                'id' => $membership->id(),
                'userId' => $membership->userId(),
                'name' => $user?->name() ?? 'Unknown user',
                'email' => $user?->email() ?? '',
                'role' => $membership->role(),
            ];
        }, $memberships);
    }

    private function mapUsers(array $users): array
    {
        return array_map(static fn (User $user): array => [
            'id' => $user->id(),
            'name' => $user->name(),
            'email' => $user->email(),
        ], $users);
    }

    private function mapView(ViewPeriodInterface $viewPeriod): array
    {
        $range = $viewPeriod->range();

        return [
            'type' => $viewPeriod->type(),
            'label' => $viewPeriod->label(),
            'start' => $range->start()->format('Y-m-d'),
            'end' => $range->end()->modify('-1 day')->format('Y-m-d'),
            'days' => array_map(
                static fn (DateTimeImmutable $day) => [
                    'date' => $day->format('Y-m-d'),
                    'day' => $day->format('j'),
                    'weekday' => $day->format('D'),
                    'month' => $day->format('M'),
                    'primary' => $viewPeriod->isPrimaryDay($day),
                ],
                $viewPeriod->days()
            ),
        ];
    }
}
