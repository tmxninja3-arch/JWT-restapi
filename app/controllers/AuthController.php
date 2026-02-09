<?php
namespace App\Controllers;

use App\Models\User;
use App\Helpers\JWT;
use App\Helpers\Response;

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function register($request) {
        $data = $request['body'];
        
        // Validate required fields
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            Response::error('Name, email and password are required', 400);
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 400);
        }
        
        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            Response::error('Email already exists', 409);
        }
        
        // Create user
        if ($this->userModel->create($data)) {
            Response::success(
                ['email' => $data['email']], 
                'User registered successfully', 
                201
            );
        } else {
            Response::error('Failed to register user', 500);
        }
    }
    
    public function login($request) {
        $data = $request['body'];
        
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password are required', 400);
        }
        
        // Find user by email
        $user = $this->userModel->findByEmail($data['email']);
        
        if (!$user) {
            Response::error('Invalid credentials', 401);
        }
        
        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            Response::error('Invalid credentials', 401);
        }
        
        // Generate JWT token
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ];
        
        $token = JWT::encode($payload);
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'expires_in' => JWT_EXPIRY
        ], 'Login successful');
    }
}