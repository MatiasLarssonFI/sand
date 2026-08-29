<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\TimeRange;
use DateInterval;
use DateTimeImmutable;

final class WeekViewPeriod implements ViewPeriodInterface
{
    private readonly DateTimeImmutable $start;

    public function __construct(DateTimeImmutable $anchor)
    {
        $this->start = $anchor->modify('monday this week')->setTime(0, 0);
    }

    public function type(): string
    {
        return 'week';
    }

    public function label(): string
    {
        return sprintf(
            '%s – %s',
            $this->start->format('j M Y'),
            $this->start->modify('+6 days')->format('j M Y')
        );
    }

    public function cursorDate(): DateTimeImmutable
    {
        return $this->start;
    }

    public function range(): TimeRange
    {
        return new TimeRange($this->start, $this->start->add(new DateInterval('P7D')));
    }

    public function days(): array
    {
        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $days[] = $this->start->modify(sprintf('+%d days', $offset));
        }

        return $days;
    }

    public function isPrimaryDay(DateTimeImmutable $day): bool
    {
        return true;
    }
}
