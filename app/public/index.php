<?php

declare(strict_types=1);

use App\Application\AppFactory;
use App\Infrastructure\Http\Request;

require dirname(__DIR__) . '/src/bootstrap.php';

$app = AppFactory::create(
    require dirname(__DIR__) . '/config/app.php',
    require dirname(__DIR__) . '/config/database.php'
);

$app->handle(Request::fromGlobals())->send();
