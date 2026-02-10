<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\PatientController;
use App\Middleware\JsonMiddleware;
use App\Middleware\AuthMiddleware;

// Initialize router
$router = new Router();

// Add middlewares
$router->addMiddleware(new JsonMiddleware());
$router->addMiddleware(new AuthMiddleware());

// Authentication routes
$router->post('/api/register', [AuthController::class, 'register']);
$router->post('/api/login', [AuthController::class, 'login']);

// Patient routes (protected)
$router->get('/api/patients', [PatientController::class, 'index']);
$router->get('/api/patients/{id}', [PatientController::class, 'show']);
$router->post('/api/patients', [PatientController::class, 'store']);
$router->put('/api/patients/{id}', [PatientController::class, 'update']);
$router->delete('/api/patients/{id}', [PatientController::class, 'destroy']);

// Dispatch request
$router->dispatch();