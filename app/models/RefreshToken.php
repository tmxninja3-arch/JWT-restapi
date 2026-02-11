<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class RefreshToken {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new refresh token
     */
    public function create($userId) {
        // Generate a random token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXPIRY);

        $sql = "INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);

        if ($stmt->execute()) {
            return $token;
        }

        return false;
    }

    /**
     * Find a valid (non-expired) refresh token
     */
    public function findValid($token) {
        $sql = "SELECT rt.*, u.id as uid, u.name, u.email 
                FROM refresh_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token = :token AND rt.expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a specific refresh token
     */
    public function deleteByToken($token) {
        $sql = "DELETE FROM refresh_tokens WHERE token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);

        return $stmt->execute();
    }

    /**
     * Delete all refresh tokens for a user
     */
    public function deleteByUserId($userId) {
        $sql = "DELETE FROM refresh_tokens WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Clean up expired tokens (optional maintenance)
     */
    public function deleteExpired() {
        $sql = "DELETE FROM refresh_tokens WHERE expires_at <= NOW()";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute();
    }
}