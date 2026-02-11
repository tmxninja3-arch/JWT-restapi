<?php
namespace App\Middleware;

use App\Helpers\JWT;
use App\Helpers\Response;

class AuthMiddleware {
    private $protectedRoutes = [
        '/api/patients',
        '/api/patients/{id}'
    ];

    // Routes that need auth but are handled separately
    private $authOptionalRoutes = [
        '/api/logout'
    ];

    public function handle(&$request) {
        $uri = $request['uri'];

        // Check if route needs protection
        $needsAuth = false;

        // Check protected routes (patients)
        foreach ($this->protectedRoutes as $route) {
            $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $route);
            if (preg_match('#^' . $pattern . '$#', $uri)) {
                $needsAuth = true;
                break;
            }
        }

        // Check logout route (also needs auth)
        $isLogout = false;
        foreach ($this->authOptionalRoutes as $route) {
            if ($uri === $route) {
                $needsAuth = true;
                $isLogout = true;
                break;
            }
        }

        if (!$needsAuth) {
            return $request;
        }

        // Check Authorization header
        $authHeader = $request['headers']['Authorization'] ?? '';

        if (empty($authHeader)) {
            // For logout, allow proceeding even without valid JWT (cookie-based logout)
            if ($isLogout) {
                return $request;
            }
            Response::error('Authorization header missing', 401);
            return false;
        }

        // Extract token
        if (strpos($authHeader, 'Bearer ') !== 0) {
            if ($isLogout) {
                return $request;
            }
            Response::error('Invalid authorization format', 401);
            return false;
        }

        $token = substr($authHeader, 7);

        // Validate JWT
        $decoded = JWT::decode($token);

        if (!$decoded) {
            if ($isLogout) {
                return $request;
            }
            Response::error('Invalid or expired token', 401);
            return false;
        }

        // Attach user data to request
        $request['user'] = $decoded;

        return $request;
    }
}