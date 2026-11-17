<?php

namespace Cards;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            // Exact match
            if ($pattern === $path) {
                $handler();
                return;
            }

            // Regex match (patterns containing parentheses)
            if (str_contains($pattern, '(')) {
                if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                    array_shift($matches);
                    $handler(...$matches);
                    return;
                }
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
