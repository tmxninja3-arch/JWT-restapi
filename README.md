<?php

/**
 * Single Entry Point
 * All requests are routed through this file
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/config/config.php';

// Load environment variables
loadEnv(BASE_PATH . '/.env');

// Load core files
require_once BASE_PATH . '/app/helpers/Response.php';
require_once BASE_PATH . '/app/helpers/JWT.php';
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/Router.php';

// Load middleware
require_once BASE_PATH . '/app/middleware/JsonMiddleware.php';
require_once BASE_PATH . '/app/middleware/AuthMiddleware.php';

// Load models
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/app/models/Patient.php';

// Load controllers
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/controllers/PatientController.php';

// Handle CORS (for development/testing)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize Router
$router = new Router();

// Initialize Controllers
$authController = new AuthController();
$patientController = new PatientController();

// ─── Public Routes (with JSON middleware only) ─────────────────────────────

$router->post('/api/register', function ($request) use ($authController) {
    $authController->register($request);
}, ['JsonMiddleware']);

$router->post('/api/login', function ($request) use ($authController) {
    $authController->login($request);
}, ['JsonMiddleware']);

// ─── Protected Routes (with JSON + Auth middleware) ────────────────────────

$router->get('/api/patients', function ($request) use ($patientController) {
    $patientController->index($request);
}, ['AuthMiddleware']);

$router->get('/api/patients/{id}', function ($request) use ($patientController) {
    $patientController->show($request);
}, ['AuthMiddleware']);

$router->post('/api/patients', function ($request) use ($patientController) {
    $patientController->store($request);
}, ['JsonMiddleware', 'AuthMiddleware']);

$router->put('/api/patients/{id}', function ($request) use ($patientController) {
    $patientController->update($request);
}, ['JsonMiddleware', 'AuthMiddleware']);

$router->delete('/api/patients/{id}', function ($request) use ($patientController) {
    $patientController->destroy($request);
}, ['AuthMiddleware']);

// ─── API Info Route ────────────────────────────────────────────────────────

$router->get('/api', function ($request) {
    Response::success([
        'name'    => 'REST API with JWT Authentication',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /api/register'        => 'Register a new user',
            'POST /api/login'           => 'Login and get JWT token',
            'GET /api/patients'         => 'Get all patients (protected)',
            'GET /api/patients/{id}'    => 'Get patient by ID (protected)',
            'POST /api/patients'        => 'Create a patient (protected)',
            'PUT /api/patients/{id}'    => 'Update a patient (protected)',
            'DELETE /api/patients/{id}' => 'Delete a patient (protected)',
        ]
    ], 'API is running');
}, []);

// ─── Resolve the Request ───────────────────────────────────────────────────

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Remove base directory from URI if running in a subdirectory
// Adjust this if your project is in a subdirectory
$basePath = '';  // e.g., '/project' if hosted at localhost/project
if (!empty($basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$router->resolve($requestMethod, $requestUri);