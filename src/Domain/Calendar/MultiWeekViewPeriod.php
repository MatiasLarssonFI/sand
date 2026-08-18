<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\TimeRange;
use DateInterval;
use DateTimeImmutable;

final class MultiWeekViewPeriod implements ViewPeriodInterface
{
    private readonly DateTimeImmutable $start;
    private readonly int $weeks;

    public function __construct(DateTimeImmutable $anchor, int $weeks)
    {
        $this->start = $anchor->modify('monday this week')->setTime(0, 0);
        $this->weeks = max(2, $weeks);
    }

    public function type(): string
    {
        return 'n-weeks';
    }

    public function label(): string
    {
        return sprintf(
            '%d weeks (%s – %s)',
            $this->weeks,
            $this->start->format('j M Y'),
            $this->start->modify(sprintf('+%d days', ($this->weeks * 7) - 1))->format('j M Y')
        );
    }

    public function range(): TimeRange
    {
        return new TimeRange($this->start, $this->start->add(new DateInterval(sprintf('P%dD', $this->weeks * 7))));
    }

    public function days(): array
    {
        $days = [];

        for ($offset = 0; $offset < ($this->weeks * 7); $offset++) {
            $days[] = $this->start->modify(sprintf('+%d days', $offset));
        }

        return $days;
    }

    public function isPrimaryDay(DateTimeImmutable $day): bool
    {
        return true;
    }
}
