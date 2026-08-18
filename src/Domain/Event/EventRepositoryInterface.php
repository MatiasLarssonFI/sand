<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\TimeRange;

interface EventRepositoryInterface
{
    /** @return Event[] */
    public function findByCalendarAndRange(int $calendarId, TimeRange $rangeUtc): array;

    public function findById(int $eventId): ?Event;

    public function save(Event $event): Event;

    public function delete(int $eventId): void;
}
