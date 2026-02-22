<?php

declare(strict_types=1);

namespace AaoSikheSystem\router;

/**
 * AaoSikheSystem Secure - Router
 * 
 * @package AaoSikheSystem
 */

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $middleware = [];
    private array $groupStack = [];
    private string $basePath = '';
    private ?string $currentRouteName = null;
    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    public function add(string $method, string $pattern, $handler, ?string $name = null): void
    {
        $pattern = $this->normalizePattern($pattern);

        $route = new Route($method, $pattern, $handler,$name);

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        $this->routes[] = $route;
    }


    // Add this helper to normalize patterns
    private function normalizePattern(string $pattern): string
    {
        $pattern = rtrim($pattern, '/');
        return $pattern === '' ? '/' : $pattern;
    }

    public function get(string $pattern, $handler, ?string $name = null): void
    {
        $this->add('GET', $pattern, $handler, $name);
    }

    public function post(string $pattern, $handler, ?string $name = null): void
    {
        $this->add('POST', $pattern, $handler, $name);
    }

    public function put(string $pattern, $handler, ?string $name = null): void
    {
        $this->add('PUT', $pattern, $handler, $name);
    }

    public function delete(string $pattern, $handler, ?string $name = null): void
    {
        $this->add('DELETE', $pattern, $handler, $name);
    }

    public function patch(string $pattern, $handler, ?string $name = null): void
    {
        $this->add('PATCH', $pattern, $handler, $name);
    }

    public function group(string $prefix, callable $callback): void
    {
        $previousBasePath = $this->basePath;
        $this->basePath .= $prefix;

        $callback($this);

        $this->basePath = $previousBasePath;
    }

    public function match(array $methods, string $pattern, $handler, ?string $name = null): void
    {
        foreach ($methods as $method) {
            $this->add($method, $pattern, $handler, $name);
        }
    }

    public function any(string $pattern, $handler, ?string $name = null): void
    {
        $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $pattern, $handler, $name);
    }

    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        return $this->namedRoutes[$name]->buildUrl($params);
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = $this->removeBasePath($uri);
        $uri = $this->normalizePattern($uri);

        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri, $params)) {
                $this->currentRouteName = $route->getName();
                 \AaoSikheSystem\view\helper\UrlHelper::setCurrentRouteName(
                $this->currentRouteName
            );
                $route->execute($params);
                return;
            }
        }

        throw new \RuntimeException("Route not found: {$method} {$uri}");
    }



    private function removeBasePath(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = rtrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }


    public function getNamedRoutes(): array
    {
        $routes = [];

        foreach ($this->namedRoutes as $name => $route) {
            $routes[$name] = $route->buildUrl([]);
        }

        return $routes;
    }

    public function getCurrentRouteName(): ?string
    {
        
        return $this->currentRouteName;
        
    }
}

class Route
{
    private string $method;
    private string $pattern;
    private $handler;
    private array $params = [];
    private ?string $name;
    public function __construct(string $method, string $pattern, $handler, ?string $name = null)
    {
        $this->method = strtoupper($method);
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->name    = $name;
    }

    public function matches(string $method, string $uri, ?array &$params): bool
    {
        if ($this->method !== $method && $this->method !== 'ANY') {
            return false;
        }

        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $this->pattern);
        $pattern = "#^{$pattern}/?$#"; // allow optional trailing slash


        if (preg_match($pattern, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function execute(array $params = []): void
    {

        if (is_callable($this->handler)) {
            call_user_func_array($this->handler, $params);
        } elseif (is_string($this->handler)) {
            $this->executeController($this->handler, $params);
        } else {
            throw new \InvalidArgumentException('Invalid route handler');
        }
    }

    private function executeController(string $handler, array $params): void
    {
        [$controller, $method] = explode('@', $handler);
        $controller = "App\\Controllers\\{$controller}";

        if (!class_exists($controller)) {
            throw new \RuntimeException("Controller {$controller} not found");
        }

        $instance = new $controller();

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method {$method} not found in controller {$controller}");
        }

        call_user_func_array([$instance, $method], $params);
    }

    public function buildUrl(array $params): string
    {
        $url = $this->pattern;

        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }

        return $url;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
}
