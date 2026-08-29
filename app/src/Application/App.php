<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Shared\AuthorizationException;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\ValidationException;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\Router;
use App\Infrastructure\Http\TemplateRenderer;
use App\Infrastructure\Support\Logger;
use Throwable;

final class App
{
    public function __construct(
        private readonly Router $router,
        private readonly TemplateRenderer $renderer,
        private readonly Logger $logger,
        private readonly array $appConfig,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (ValidationException $exception) {
            return $this->errorResponse($request, 422, $exception->getMessage());
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($request, 403, $exception->getMessage());
        } catch (NotFoundException $exception) {
            return $this->errorResponse($request, 404, $exception->getMessage());
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);

            return $this->errorResponse(
                $request,
                500,
                $this->appConfig['debug'] ? $throwable->getMessage() : 'An unexpected error occurred.'
            );
        }
    }

    private function errorResponse(Request $request, int $status, string $message): Response
    {
        if (str_starts_with($request->path(), '/api/')) {
            return Response::json(['error' => $message], $status);
        }

        return Response::html($this->renderer->render('error.php', [
            'status' => $status,
            'message' => $message,
        ]), $status);
    }
}
