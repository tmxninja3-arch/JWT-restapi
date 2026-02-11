<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\RefreshToken;
use App\Helpers\JWT;
use App\Helpers\Response;

class AuthController {
    private $userModel;
    private $refreshTokenModel;

    public function __construct() {
        $this->userModel = new User();
        $this->refreshTokenModel = new RefreshToken();
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

        // Generate Access Token (JWT, 15 minutes)
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ];
        $accessToken = JWT::encode($payload);

        // Generate Refresh Token (7 days) and store in DB
        $refreshToken = $this->refreshTokenModel->create($user['id']);

        if (!$refreshToken) {
            Response::error('Failed to generate refresh token', 500);
        }

        // Set refresh token in HttpOnly cookie
        $this->setRefreshTokenCookie($refreshToken);

        // Return access token in JSON (refresh token is NOT in JSON)
        Response::success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRY,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ], 'Login successful');
    }

    /**
     * Refresh access token using refresh token from cookie
     */
    public function refresh($request) {
        // Read refresh token from cookie
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if (!$refreshToken) {
            Response::error('Refresh token not found. Please login again.', 401);
        }

        // Validate refresh token from database
        $tokenData = $this->refreshTokenModel->findValid($refreshToken);

        if (!$tokenData) {
            // Clear the invalid cookie
            $this->clearRefreshTokenCookie();
            Response::error('Invalid or expired refresh token. Please login again.', 401);
        }

        // Delete the old refresh token (rotate)
        $this->refreshTokenModel->deleteByToken($refreshToken);

        // Generate new access token
        $payload = [
            'user_id' => $tokenData['uid'],
            'email' => $tokenData['email'],
            'name' => $tokenData['name']
        ];
        $accessToken = JWT::encode($payload);

        // Generate new refresh token (token rotation)
        $newRefreshToken = $this->refreshTokenModel->create($tokenData['uid']);

        if (!$newRefreshToken) {
            Response::error('Failed to generate refresh token', 500);
        }

        // Set new refresh token in HttpOnly cookie
        $this->setRefreshTokenCookie($newRefreshToken);

        Response::success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRY
        ], 'Token refreshed successfully');
    }

    /**
     * Logout - delete refresh token from DB and clear cookie
     */
    public function logout($request) {
        // Read refresh token from cookie
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if ($refreshToken) {
            // Delete from database
            $this->refreshTokenModel->deleteByToken($refreshToken);
        }

        // If user is authenticated via JWT, delete all their refresh tokens (optional: more secure)
        if (isset($request['user']['user_id'])) {
            $this->refreshTokenModel->deleteByUserId($request['user']['user_id']);
        }

        // Clear the cookie
        $this->clearRefreshTokenCookie();

        Response::success(null, 'Logged out successfully');
    }

    /**
     * Set refresh token as HttpOnly cookie
     */
    private function setRefreshTokenCookie($token) {
        setcookie('refresh_token', $token, [
            'expires' => time() + REFRESH_TOKEN_EXPIRY,
            'path' => '/',
            'domain' => '',
            'secure' => false,    // Set to true in production with HTTPS
            'httponly' => true,    // Not accessible via JavaScript
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Clear refresh token cookie
     */
    private function clearRefreshTokenCookie() {
        setcookie('refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}