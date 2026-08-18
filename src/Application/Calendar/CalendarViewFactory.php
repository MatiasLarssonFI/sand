<?php

declare(strict_types=1);

namespace App\Application\Calendar;

use App\Domain\Calendar\DayViewPeriod;
use App\Domain\Calendar\MonthViewPeriod;
use App\Domain\Calendar\MultiWeekViewPeriod;
use App\Domain\Calendar\ViewPeriodInterface;
use App\Domain\Calendar\WeekViewPeriod;
use App\Domain\Shared\ValidationException;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class CalendarViewFactory
{
    public function create(string $view, string $date, string $timezone, int $weeks): ViewPeriodInterface
    {
        $anchor = $this->parseDate($date, $timezone);

        return match ($view) {
            'day' => new DayViewPeriod($anchor),
            'week' => new WeekViewPeriod($anchor),
            'month' => new MonthViewPeriod($anchor),
            'n-weeks' => new MultiWeekViewPeriod($anchor, $weeks),
            default => throw new ValidationException('Unsupported calendar view.'),
        };
    }

    private function parseDate(string $date, string $timezone): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($date, new DateTimeZone($timezone));
        } catch (Throwable) {
            throw new ValidationException('Invalid calendar date.');
        }
    }
}
