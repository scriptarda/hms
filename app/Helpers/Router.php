<?php
namespace App\Helpers;

/**
 * Lightweight Request Router
 * Supports GET/POST, URL parameters, middleware groups
 */
class Router
{
    private array $routes = [];
    private array $middlewareGroups = [];
    private string $currentPrefix = '';
    private array $currentMiddleware = [];

    /**
     * Register a GET route
     */
    public function get(string $path, string $action, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $action, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string $action, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $action, $middleware);
    }

    /**
     * Group routes with shared prefix and middleware
     */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        if (isset($options['prefix'])) {
            $this->currentPrefix .= '/' . trim($options['prefix'], '/');
        }

        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        }

        $callback($this);

        $this->currentPrefix = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, string $action, array $middleware = []): self
    {
        $fullPath = $this->currentPrefix . '/' . trim($path, '/');
        $fullPath = rtrim($fullPath, '/') ?: '/';

        // Merge middleware
        $allMiddleware = array_merge($this->currentMiddleware, $middleware);

        // Convert URL params like {id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'pattern'    => $pattern,
            'action'     => $action,
            'middleware'  => $allMiddleware,
        ];

        return $this;
    }

    /**
     * Dispatch the request to the appropriate controller
     */
    public function dispatch(string $url, string $method): void
    {
        $url = '/' . ltrim($url, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $url, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middlewareFull = 'App\\Middleware\\' . $middlewareClass;
                    if (class_exists($middlewareFull)) {
                        $middlewareInstance = new $middlewareFull();
                        if (!$middlewareInstance->handle()) {
                            return;
                        }
                    }
                }

                // Parse controller@method
                [$controllerName, $methodName] = explode('@', $route['action']);
                $controllerClass = 'App\\Controllers\\' . $controllerName;

                if (!class_exists($controllerClass)) {
                    http_response_code(404);
                    echo '<h1>404 - Controller Not Found</h1>';
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $methodName)) {
                    http_response_code(404);
                    echo '<h1>404 - Method Not Found</h1>';
                    return;
                }

                // Call the controller method with parameters
                call_user_func_array([$controller, $methodName], $params);
                return;
            }
        }

        // No route matched
        http_response_code(404);
        include APP_PATH . '/Views/errors/404.php';
    }

    /**
     * Generate URL for a named route (simple version using path)
     */
    public static function url(string $path, array $params = []): string
    {
        $baseUrl = $GLOBALS['appConfig']['url'] ?? '';
        $path = '/' . ltrim($path, '/');
        
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }

        return $baseUrl . $path;
    }
}
