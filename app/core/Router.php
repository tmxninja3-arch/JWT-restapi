<?php
namespace App\Core;

class Router {
    private $routes = [];
    private $middlewares = [];
    
    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }
    
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function put($path, $handler) {
        $this->routes['PUT'][$path] = $handler;
    }
    
    public function delete($path, $handler) {
        $this->routes['DELETE'][$path] = $handler;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if project is in subdirectory
        $basePath = '/project-jwt';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        $uri = $uri ?: '/';
        
        // Initialize request object
        $request = [
            'method' => $method,
            'uri' => $uri,
            'headers' => getallheaders(),
            'body' => null
        ];
        
        // Run middlewares
        foreach ($this->middlewares as $middleware) {
            $result = $middleware->handle($request);
            if ($result === false) {
                return;
            }
            if (is_array($result)) {
                $request = $result;
            }
        }
        
        // Check for exact match
        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            $params = [];
        } else {
            // Check for dynamic routes
            $handler = null;
            $params = [];
            
            foreach ($this->routes[$method] ?? [] as $route => $routeHandler) {
                $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
                $pattern = '#^' . $pattern . '$#';
                
                if (preg_match($pattern, $uri, $matches)) {
                    $handler = $routeHandler;
                    array_shift($matches);
                    $params = $matches;
                    break;
                }
            }
        }
        
        if ($handler) {
            if (is_array($handler)) {
                $controller = new $handler[0]();
                $method = $handler[1];
                call_user_func_array([$controller, $method], array_merge([$request], $params));
            } else {
                call_user_func_array($handler, array_merge([$request], $params));
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
        }
    }
}