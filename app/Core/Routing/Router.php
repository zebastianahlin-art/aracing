<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Response;

final class Router
{
    /** @var array<string, array<int, array{pattern:string, handler:callable}>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][] = ['pattern' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][] = ['pattern' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): Response
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            $params = [];

            if ($this->matches($route['pattern'], $path, $params)) {
                return ($route['handler'])(...array_values($params));
            }
        }

        return new Response('Sidan kunde inte hittas.', 404);
    }

    private function matches(string $pattern, string $path, array &$params): bool
    {
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return true;
    }
}
