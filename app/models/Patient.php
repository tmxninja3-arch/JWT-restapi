<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Patient {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all patients for a specific user
     */
    public function getAllByUserId($userId) {
        $sql = "SELECT * FROM patients WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a patient by ID (without user filtering - used for ownership check)
     */
    public function getById($id) {
        $sql = "SELECT * FROM patients WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get a patient by ID that belongs to a specific user
     */
    public function getByIdAndUserId($id, $userId) {
        $sql = "SELECT * FROM patients WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a patient linked to a user
     */
    public function create($data, $userId) {
        $sql = "INSERT INTO patients (user_id, name, age, gender, phone, address) 
                VALUES (:user_id, :name, :age, :gender, :phone, :address)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':age', $data['age']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update a patient (only if it belongs to the user)
     */
    public function updateByIdAndUserId($id, $userId, $data) {
        $sql = "UPDATE patients 
                SET name = :name, age = :age, gender = :gender, 
                    phone = :phone, address = :address 
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':age', $data['age']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);

        return $stmt->execute();
    }

    /**
     * Delete a patient (only if it belongs to the user)
     */
    public function deleteByIdAndUserId($id, $userId) {
        $sql = "DELETE FROM patients WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }
}