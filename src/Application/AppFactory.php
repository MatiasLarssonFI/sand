<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Calendar\CalendarQueryService;
use App\Application\Calendar\CalendarViewFactory;
use App\Application\Calendar\MembershipService;
use App\Application\Event\EventService;
use App\Application\Security\AccessService;
use App\Infrastructure\Http\CalendarApiController;
use App\Infrastructure\Http\EventApiController;
use App\Infrastructure\Http\HomeController;
use App\Infrastructure\Http\MembershipApiController;
use App\Infrastructure\Http\Router;
use App\Infrastructure\Http\TemplateRenderer;
use App\Infrastructure\Persistence\PdoCalendarRepository;
use App\Infrastructure\Persistence\PdoConnection;
use App\Infrastructure\Persistence\PdoEventRepository;
use App\Infrastructure\Persistence\PdoTransactionManager;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Security\SessionCsrfTokenManager;
use App\Infrastructure\Security\SessionCurrentUserProvider;
use App\Infrastructure\Support\Logger;

final class AppFactory
{
    public static function create(array $appConfig, array $databaseConfig): App
    {
        date_default_timezone_set($appConfig['timezone']);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }

        $pdo = PdoConnection::create($databaseConfig);
        $calendarRepository = new PdoCalendarRepository($pdo);
        $eventRepository = new PdoEventRepository($pdo);
        $userRepository = new PdoUserRepository($pdo);
        $transactionManager = new PdoTransactionManager($pdo);
        $accessService = new AccessService($calendarRepository);
        $viewFactory = new CalendarViewFactory();
        $calendarQueryService = new CalendarQueryService(
            $calendarRepository,
            $eventRepository,
            $userRepository,
            $accessService,
            $viewFactory,
            $appConfig
        );
        $eventService = new EventService($eventRepository, $calendarRepository, $accessService, $transactionManager);
        $membershipService = new MembershipService(
            $calendarRepository,
            $userRepository,
            $accessService,
            $transactionManager
        );
        $currentUserProvider = new SessionCurrentUserProvider($userRepository);
        $csrfTokenManager = new SessionCsrfTokenManager();
        $renderer = new TemplateRenderer(dirname(__DIR__, 2) . '/templates', [
            'appConfig' => $appConfig,
        ]);
        $logger = new Logger($appConfig['log_file']);

        $router = new Router();
        $homeController = new HomeController(
            $renderer,
            $calendarQueryService,
            $currentUserProvider,
            $csrfTokenManager,
            $appConfig
        );
        $calendarController = new CalendarApiController(
            $calendarQueryService,
            $currentUserProvider,
            $csrfTokenManager
        );
        $eventController = new EventApiController(
            $eventService,
            $calendarQueryService,
            $currentUserProvider,
            $csrfTokenManager
        );
        $membershipController = new MembershipApiController(
            $membershipService,
            $currentUserProvider,
            $csrfTokenManager
        );

        $router->add('GET', '/', $homeController);
        $router->add('GET', '/api/calendar', [$calendarController, 'show']);
        $router->add('POST', '/api/switch-user', [$calendarController, 'switchUser']);
        $router->add('GET', '/api/events/{id}', [$eventController, 'detail']);
        $router->add('POST', '/api/events', [$eventController, 'create']);
        $router->add('POST', '/api/events/{id}', [$eventController, 'update']);
        $router->add('DELETE', '/api/events/{id}', [$eventController, 'delete']);
        $router->add('POST', '/api/memberships', [$membershipController, 'create']);
        $router->add('POST', '/api/memberships/{id}', [$membershipController, 'update']);
        $router->add('DELETE', '/api/memberships/{id}', [$membershipController, 'delete']);

        return new App($router, $renderer, $logger, $appConfig);
    }
}
