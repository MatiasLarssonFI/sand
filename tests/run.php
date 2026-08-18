<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Application\Calendar\CalendarViewFactory;
use App\Application\Calendar\MembershipService;
use App\Application\Event\EventService;
use App\Application\Security\AccessService;
use App\Domain\Calendar\Calendar;
use App\Domain\Calendar\CalendarMember;
use App\Domain\Calendar\CalendarRepositoryInterface;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Shared\AuthorizationException;
use App\Domain\Shared\TimeRange;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Shared\ValidationException;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;

final class InMemoryTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return $callback();
    }
}

final class InMemoryCalendarRepository implements CalendarRepositoryInterface
{
    /** @param Calendar[] $calendars @param CalendarMember[] $memberships */
    public function __construct(private array $calendars, private array $memberships)
    {
    }

    public function findAccessibleByUserId(int $userId): array
    {
        $ids = array_map(
            static fn (CalendarMember $member): int => $member->calendarId(),
            array_filter($this->memberships, static fn (CalendarMember $member): bool => $member->userId() === $userId)
        );

        return array_values(array_filter($this->calendars, static fn (Calendar $calendar): bool => in_array($calendar->id(), $ids, true)));
    }

    public function findById(int $calendarId): ?Calendar
    {
        foreach ($this->calendars as $calendar) {
            if ($calendar->id() === $calendarId) {
                return $calendar;
            }
        }

        return null;
    }

    public function findMemberships(int $calendarId): array
    {
        return array_values(array_filter($this->memberships, static fn (CalendarMember $member): bool => $member->calendarId() === $calendarId));
    }

    public function findMembership(int $calendarId, int $userId): ?CalendarMember
    {
        foreach ($this->memberships as $member) {
            if ($member->calendarId() === $calendarId && $member->userId() === $userId) {
                return $member;
            }
        }

        return null;
    }

    public function findMembershipById(int $membershipId): ?CalendarMember
    {
        foreach ($this->memberships as $member) {
            if ($member->id() === $membershipId) {
                return $member;
            }
        }

        return null;
    }

    public function createMembership(int $calendarId, int $userId, string $role): CalendarMember
    {
        $member = new CalendarMember(count($this->memberships) + 1, $calendarId, $userId, $role);
        $this->memberships[] = $member;

        return $member;
    }

    public function updateMembershipRole(int $membershipId, string $role): void
    {
        foreach ($this->memberships as $index => $member) {
            if ($member->id() === $membershipId) {
                $this->memberships[$index] = new CalendarMember($member->id(), $member->calendarId(), $member->userId(), $role);
            }
        }
    }

    public function deleteMembership(int $membershipId): void
    {
        $this->memberships = array_values(array_filter(
            $this->memberships,
            static fn (CalendarMember $member): bool => $member->id() !== $membershipId
        ));
    }

    public function countOwners(int $calendarId): int
    {
        return count(array_filter(
            $this->memberships,
            static fn (CalendarMember $member): bool => $member->calendarId() === $calendarId && $member->isOwner()
        ));
    }
}

final class InMemoryEventRepository implements EventRepositoryInterface
{
    /** @param Event[] $events */
    public function __construct(private array $events)
    {
    }

    public function findByCalendarAndRange(int $calendarId, TimeRange $rangeUtc): array
    {
        return array_values(array_filter($this->events, static function (Event $event) use ($calendarId, $rangeUtc): bool {
            return $event->calendarId() === $calendarId && $event->timeRangeUtc()->intersects($rangeUtc);
        }));
    }

    public function findById(int $eventId): ?Event
    {
        foreach ($this->events as $event) {
            if ($event->id() === $eventId) {
                return $event;
            }
        }

        return null;
    }

    public function save(Event $event): Event
    {
        if ($event->id() === null) {
            $event = new Event(count($this->events) + 1, $event->calendarId(), $event->title(), $event->description(), $event->timeRangeUtc(), $event->createdByUserId(), $event->updatedByUserId());
            $this->events[] = $event;

            return $event;
        }

        foreach ($this->events as $index => $existing) {
            if ($existing->id() === $event->id()) {
                $this->events[$index] = $event;
            }
        }

        return $event;
    }

    public function delete(int $eventId): void
    {
        $this->events = array_values(array_filter($this->events, static fn (Event $event): bool => $event->id() !== $eventId));
    }
}

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @param User[] $users */
    public function __construct(private array $users)
    {
    }

    public function findAll(): array
    {
        return $this->users;
    }

    public function findById(int $userId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id() === $userId) {
                return $user;
            }
        }

        return null;
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertThrows(callable $callback, string $exceptionClass, string $message): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        assertTrue($throwable instanceof $exceptionClass, $message);

        return;
    }

    throw new RuntimeException($message);
}

$calendar = new Calendar(1, 'Team Calendar', 'UTC');
$memberships = [
    new CalendarMember(1, 1, 1, CalendarMember::ROLE_OWNER),
    new CalendarMember(2, 1, 2, CalendarMember::ROLE_EDITOR),
    new CalendarMember(3, 1, 3, CalendarMember::ROLE_VIEWER),
];
$users = [
    new User(1, 'Owner', 'owner@example.test'),
    new User(2, 'Editor', 'editor@example.test'),
    new User(3, 'Viewer', 'viewer@example.test'),
];
$existingEvent = new Event(
    1,
    1,
    'Existing',
    'Existing event',
    new TimeRange(new DateTimeImmutable('2026-08-19 09:00:00', new DateTimeZone('UTC')), new DateTimeImmutable('2026-08-19 10:00:00', new DateTimeZone('UTC'))),
    1,
    1,
);

$calendarRepository = new InMemoryCalendarRepository([$calendar], $memberships);
$userRepository = new InMemoryUserRepository($users);
$eventRepository = new InMemoryEventRepository([$existingEvent]);
$accessService = new AccessService($calendarRepository);
$eventService = new EventService($eventRepository, $calendarRepository, $accessService, new InMemoryTransactionManager());
$membershipService = new MembershipService($calendarRepository, $userRepository, $accessService, new InMemoryTransactionManager());
$viewFactory = new CalendarViewFactory();

$monthView = $viewFactory->create('month', '2026-08-18', 'UTC', 4);
assertTrue(count($monthView->days()) >= 28, 'Month view should include day slots.');

$multiWeekView = $viewFactory->create('n-weeks', '2026-08-18', 'UTC', 6);
assertTrue(count($multiWeekView->days()) === 42, 'N-week view should expose seven days per week.');

$created = $eventService->create(2, [
    'calendarId' => 1,
    'title' => 'Retro',
    'description' => 'Sprint retrospective',
    'start' => '2026-08-19T10:30',
    'end' => '2026-08-19T11:30',
]);
assertTrue($created->id() !== null, 'Editors should be able to create events.');

assertThrows(
    fn () => $eventService->create(3, [
        'calendarId' => 1,
        'title' => 'Blocked',
        'description' => '',
        'start' => '2026-08-20T09:00',
        'end' => '2026-08-20T10:00',
    ]),
    AuthorizationException::class,
    'Viewers must not create events.'
);

assertThrows(
    fn () => $eventService->create(1, [
        'calendarId' => 1,
        'title' => 'Overlap',
        'description' => '',
        'start' => '2026-08-19T09:30',
        'end' => '2026-08-19T10:30',
    ]),
    ValidationException::class,
    'Overlapping events should be rejected.'
);

$membershipService->addMember(1, 1, 2, CalendarMember::ROLE_OWNER);
assertTrue($calendarRepository->countOwners(1) === 2, 'Owners should be able to add another owner.');

assertThrows(
    fn () => $membershipService->removeMember(1, 1),
    ValidationException::class,
    'Removing the last owner should be rejected.'
);

echo "All tests passed.\n";
