<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;

final class TimeRange
{
    public function __construct(
        private readonly DateTimeImmutable $start,
        private readonly DateTimeImmutable $end,
    ) {
        if ($end <= $start) {
            throw new ValidationException('The end time must be after the start time.');
        }
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    public function intersects(self $other): bool
    {
        return $this->start < $other->end() && $this->end > $other->start();
    }

    public function inTimezone(DateTimeZone $timezone): self
    {
        return new self(
            $this->start->setTimezone($timezone),
            $this->end->setTimezone($timezone),
        );
    }
}
