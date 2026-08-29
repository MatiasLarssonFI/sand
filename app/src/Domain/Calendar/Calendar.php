<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

final class Calendar
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $timezone,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }
}
