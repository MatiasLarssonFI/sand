<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Shared\TimeRange;
use DateTimeImmutable;

interface ViewPeriodInterface
{
    public function type(): string;

    public function label(): string;

    public function range(): TimeRange;

    /** @return DateTimeImmutable[] */
    public function days(): array;

    public function isPrimaryDay(DateTimeImmutable $day): bool;
}
