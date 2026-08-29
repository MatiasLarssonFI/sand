<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\TimeRange;
use DateInterval;
use DateTimeImmutable;

final class MonthViewPeriod implements ViewPeriodInterface
{
    private readonly DateTimeImmutable $monthStart;
    private readonly DateTimeImmutable $gridStart;
    private readonly DateTimeImmutable $gridEnd;

    public function __construct(DateTimeImmutable $anchor)
    {
        $this->monthStart = $anchor->modify('first day of this month')->setTime(0, 0);
        $this->gridStart = $this->monthStart->modify('monday this week')->setTime(0, 0);
        $this->gridEnd = $this->monthStart->modify('last day of this month')->modify('sunday this week')->setTime(0, 0);
    }

    public function type(): string
    {
        return 'month';
    }

    public function label(): string
    {
        return $this->monthStart->format('F Y');
    }

    public function cursorDate(): DateTimeImmutable
    {
        return $this->monthStart;
    }

    public function range(): TimeRange
    {
        return new TimeRange($this->gridStart, $this->gridEnd->add(new DateInterval('P1D')));
    }

    public function days(): array
    {
        $days = [];
        $current = $this->gridStart;

        while ($current <= $this->gridEnd) {
            $days[] = $current;
            $current = $current->add(new DateInterval('P1D'));
        }

        return $days;
    }

    public function isPrimaryDay(DateTimeImmutable $day): bool
    {
        return $day->format('Y-m') === $this->monthStart->format('Y-m');
    }
}
