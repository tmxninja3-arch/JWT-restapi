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
     * Create a new refresh token (generate plain token, hash it, store hash)
     */
    public function create($userId) {
        // Generate a random plain token (32 bytes -> 64 hex chars)
        $plainToken = bin2hex(random_bytes(32));
        
        // Hash the token using HMAC-SHA256 with JWT_SECRET
        $tokenHash = hash_hmac('sha256', $plainToken, JWT_SECRET);
        
        $expiresAt = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXPIRY);

        $sql = "INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':expires_at', $expiresAt);

        if ($stmt->execute()) {
            // Return the plain token (for cookie), not the hash
            return $plainToken;
        }

        return false;
    }

    /**
     * Find a valid (non-expired) refresh token by hashing the provided token and matching the hash
     */
    public function findValid($plainToken) {
        // Hash the provided plain token
        $tokenHash = hash_hmac('sha256', $plainToken, JWT_SECRET);
        
        $sql = "SELECT rt.*, u.id as uid, u.name, u.email 
                FROM refresh_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token_hash = :token_hash AND rt.expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a specific refresh token by hashing the provided token and matching the hash
     */
    public function deleteByToken($plainToken) {
        // Hash the provided plain token
        $tokenHash = hash_hmac('sha256', $plainToken, JWT_SECRET);
        
        $sql = "DELETE FROM refresh_tokens WHERE token_hash = :token_hash";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token_hash', $tokenHash);

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