<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Application\Security\AccessService;
use App\Domain\Calendar\CalendarRepositoryInterface;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\TimeRange;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Shared\ValidationException;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly CalendarRepositoryInterface $calendarRepository,
        private readonly AccessService $accessService,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function detail(int $actorId, int $eventId): Event
    {
        $event = $this->eventRepository->findById($eventId);

        if ($event === null) {
            throw new NotFoundException('Event not found.');
        }

        $this->accessService->assertCanView($event->calendarId(), $actorId);

        return $event;
    }

    public function create(int $actorId, array $payload): Event
    {
        $calendarId = $this->requirePositiveInt($payload['calendarId'] ?? null, 'Calendar is required.');
        $calendar = $this->calendarRepository->findById($calendarId);

        if ($calendar === null) {
            throw new NotFoundException('Calendar not found.');
        }

        $this->accessService->assertCanEdit($calendarId, $actorId);

        $event = new Event(
            null,
            $calendarId,
            $this->requireTitle($payload['title'] ?? ''),
            trim((string) ($payload['description'] ?? '')),
            $this->buildRange((string) ($payload['start'] ?? ''), (string) ($payload['end'] ?? ''), $calendar->timezone()),
            $actorId,
            $actorId,
        );

        $this->assertNoOverlap($event);

        return $this->transactionManager->run(fn (): Event => $this->eventRepository->save($event));
    }

    public function update(int $actorId, int $eventId, array $payload): Event
    {
        $existing = $this->detail($actorId, $eventId);
        $calendar = $this->calendarRepository->findById($existing->calendarId());

        if ($calendar === null) {
            throw new NotFoundException('Calendar not found.');
        }

        $this->accessService->assertCanEdit($calendar->id(), $actorId);

        $event = new Event(
            $existing->id(),
            $existing->calendarId(),
            $this->requireTitle($payload['title'] ?? ''),
            trim((string) ($payload['description'] ?? '')),
            $this->buildRange((string) ($payload['start'] ?? ''), (string) ($payload['end'] ?? ''), $calendar->timezone()),
            $existing->createdByUserId(),
            $actorId,
        );

        $this->assertNoOverlap($event);

        return $this->transactionManager->run(fn (): Event => $this->eventRepository->save($event));
    }

    public function delete(int $actorId, int $eventId): void
    {
        $event = $this->detail($actorId, $eventId);

        $this->accessService->assertCanEdit($event->calendarId(), $actorId);

        $this->transactionManager->run(function () use ($eventId): void {
            $this->eventRepository->delete($eventId);
        });
    }

    private function requirePositiveInt(mixed $value, string $message): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($number === false) {
            throw new ValidationException($message);
        }

        return $number;
    }

    private function requireTitle(mixed $value): string
    {
        $title = trim((string) $value);

        if ($title === '') {
            throw new ValidationException('Event title is required.');
        }

        if (mb_strlen($title) > 120) {
            throw new ValidationException('Event title must be 120 characters or fewer.');
        }

        return $title;
    }

    private function buildRange(string $start, string $end, string $timezone): TimeRange
    {
        $tz = new DateTimeZone($timezone);
        $startAt = $this->parseLocalDateTime($start, $tz);
        $endAt = $this->parseLocalDateTime($end, $tz);

        return new TimeRange(
            $startAt->setTimezone(new DateTimeZone('UTC')),
            $endAt->setTimezone(new DateTimeZone('UTC')),
        );
    }

    private function parseLocalDateTime(string $value, DateTimeZone $timezone): DateTimeImmutable
    {
        try {
            $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, $timezone);

            if ($date === false) {
                throw new ValidationException('Invalid event date.');
            }

            return $date;
        } catch (Throwable) {
            throw new ValidationException('Invalid event date.');
        }
    }

    private function assertNoOverlap(Event $candidate): void
    {
        $events = $this->eventRepository->findByCalendarAndRange(
            $candidate->calendarId(),
            $candidate->timeRangeUtc()
        );

        foreach ($events as $event) {
            if ($candidate->id() !== null && $candidate->id() === $event->id()) {
                continue;
            }

            if ($candidate->timeRangeUtc()->intersects($event->timeRangeUtc())) {
                throw new ValidationException('Events may not overlap in the same calendar.');
            }
        }
    }
}
