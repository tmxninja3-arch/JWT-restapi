<?php
namespace App\Middleware;

use App\Helpers\JWT;
use App\Helpers\Response;

class AuthMiddleware {
    private $protectedRoutes = [
        '/api/patients',
        '/api/patients/{id}'
    ];
    
    public function handle(&$request) {
        $uri = $request['uri'];
        
        // Check if route needs protection
        $needsAuth = false;
        foreach ($this->protectedRoutes as $route) {
            $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $route);
            if (preg_match('#^' . $pattern . '$#', $uri)) {
                $needsAuth = true;
                break;
            }
        }
        
        if (!$needsAuth) {
            return $request;
        }
        
        // Check Authorization header
        $authHeader = $request['headers']['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            Response::error('Authorization header missing', 401);
            return false;
        }
        
        // Extract token
        if (strpos($authHeader, 'Bearer ') !== 0) {
            Response::error('Invalid authorization format', 401);
            return false;
        }
        
        $token = substr($authHeader, 7);
        
        // Validate JWT
        $decoded = JWT::decode($token);
        
        if (!$decoded) {
            Response::error('Invalid or expired token', 401);
            return false;
        }
        
        // Attach user data to request
        $request['user'] = $decoded;
        
        return $request;
    }
}