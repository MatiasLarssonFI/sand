<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\TimeRange;

final class Event
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $calendarId,
        private readonly string $title,
        private readonly string $description,
        private readonly TimeRange $timeRangeUtc,
        private readonly int $createdByUserId,
        private readonly int $updatedByUserId,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function calendarId(): int
    {
        return $this->calendarId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function timeRangeUtc(): TimeRange
    {
        return $this->timeRangeUtc;
    }

    public function createdByUserId(): int
    {
        return $this->createdByUserId;
    }

    public function updatedByUserId(): int
    {
        return $this->updatedByUserId;
    }
}
