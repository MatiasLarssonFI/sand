<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Shared\NotFoundException;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [$method, $pattern, $handler];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as [$method, $pattern, $handler]) {
            if ($method !== $request->method()) {
                continue;
            }

            $regex = '#^' . preg_replace('#\{[^/]+\}#', '([^/]+)', $pattern) . '$#';

            if (preg_match($regex, $request->path(), $matches) !== 1) {
                continue;
            }

            array_shift($matches);

            return $handler($request, ...array_map('urldecode', $matches));
        }

        throw new NotFoundException('Route not found.');
    }
}
