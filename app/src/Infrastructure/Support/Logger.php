<?php

declare(strict_types=1);

namespace App\Infrastructure\Support;

use Throwable;

final class Logger
{
    public function __construct(private readonly string $logFile)
    {
    }

    public function error(Throwable $throwable): void
    {
        $directory = dirname($this->logFile);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log(sprintf('Unable to create log directory: %s', $directory));

            return;
        }

        $message = sprintf(
            "[%s] %s in %s:%d\n%s\n\n",
            date(DATE_ATOM),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        );

        if (file_put_contents($this->logFile, $message, FILE_APPEND) === false) {
            error_log(sprintf('Unable to write log file: %s', $this->logFile));
        }
    }
}
