<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'Shared Calendar',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
    'session_secure_cookie' => filter_var(getenv('APP_SESSION_SECURE_COOKIE') ?: '0', FILTER_VALIDATE_BOOL),
    'base_url' => rtrim(getenv('APP_BASE_URL') ?: '', '/'),
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    'default_view' => getenv('APP_DEFAULT_VIEW') ?: 'month',
    'default_weeks' => max(2, (int) (getenv('APP_DEFAULT_WEEKS') ?: 4)),
    'log_file' => getenv('APP_LOG_FILE') ?: __DIR__ . '/../storage/logs/app.log',
];
