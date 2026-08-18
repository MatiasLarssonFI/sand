<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\TimeRange;
use DateInterval;
use DateTimeImmutable;

final class DayViewPeriod implements ViewPeriodInterface
{
    public function __construct(private readonly DateTimeImmutable $anchor)
    {
    }

    public function type(): string
    {
        return 'day';
    }

    public function label(): string
    {
        return $this->anchor->format('D, j M Y');
    }

    public function range(): TimeRange
    {
        $start = $this->anchor->setTime(0, 0);

        return new TimeRange($start, $start->add(new DateInterval('P1D')));
    }

    public function days(): array
    {
        return [$this->anchor->setTime(0, 0)];
    }

    public function isPrimaryDay(DateTimeImmutable $day): bool
    {
        return $day->format('Y-m-d') === $this->anchor->format('Y-m-d');
    }
}
