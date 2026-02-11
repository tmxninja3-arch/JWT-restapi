<?php
namespace App\Controllers;

use App\Models\Patient;
use App\Helpers\Response;

class PatientController {
    private $patientModel;

    public function __construct() {
        $this->patientModel = new Patient();
    }

    /**
     * Get logged-in user's ID from request
     */
    private function getUserId($request) {
        return $request['user']['user_id'] ?? null;
    }

    /**
     * List all patients belonging to the logged-in user
     */
    public function index($request) {
        $userId = $this->getUserId($request);
        $patients = $this->patientModel->getAllByUserId($userId);
        Response::success($patients, 'Patients retrieved successfully');
    }

    /**
     * Show a single patient - only if it belongs to the logged-in user
     */
    public function show($request, $id) {
        $userId = $this->getUserId($request);

        // First check if patient exists at all
        $patient = $this->patientModel->getById($id);

        if (!$patient) {
            Response::error('Patient not found', 404);
        }

        // Check ownership
        if ((int)$patient['user_id'] !== (int)$userId) {
            Response::error('Forbidden: You do not have access to this patient', 403);
        }

        Response::success($patient, 'Patient retrieved successfully');
    }

    /**
     * Create a new patient linked to the logged-in user
     */
    public function store($request) {
        $data = $request['body'];
        $userId = $this->getUserId($request);

        // Validate required fields
        $required = ['name', 'age', 'gender', 'phone', 'address'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Response::error("Field '$field' is required", 400);
            }
        }

        // Validate age
        if (!is_numeric($data['age']) || $data['age'] < 0 || $data['age'] > 150) {
            Response::error('Invalid age', 400);
        }

        // Validate gender
        if (!in_array(strtolower($data['gender']), ['male', 'female', 'other'])) {
            Response::error('Gender must be male, female, or other', 400);
        }

        // Create patient with user_id
        $patientId = $this->patientModel->create($data, $userId);

        if ($patientId) {
            $patient = $this->patientModel->getByIdAndUserId($patientId, $userId);
            Response::success($patient, 'Patient created successfully', 201);
        } else {
            Response::error('Failed to create patient', 500);
        }
    }

    /**
     * Update a patient - only if it belongs to the logged-in user
     */
    public function update($request, $id) {
        $data = $request['body'];
        $userId = $this->getUserId($request);

        // Check if patient exists at all
        $patient = $this->patientModel->getById($id);
        if (!$patient) {
            Response::error('Patient not found', 404);
        }

        // Check ownership
        if ((int)$patient['user_id'] !== (int)$userId) {
            Response::error('Forbidden: You do not have access to this patient', 403);
        }

        // Validate required fields
        $required = ['name', 'age', 'gender', 'phone', 'address'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Response::error("Field '$field' is required", 400);
            }
        }

        // Update patient
        if ($this->patientModel->updateByIdAndUserId($id, $userId, $data)) {
            $updatedPatient = $this->patientModel->getByIdAndUserId($id, $userId);
            Response::success($updatedPatient, 'Patient updated successfully');
        } else {
            Response::error('Failed to update patient', 500);
        }
    }

    /**
     * Delete a patient - only if it belongs to the logged-in user
     */
    public function destroy($request, $id) {
        $userId = $this->getUserId($request);

        // Check if patient exists at all
        $patient = $this->patientModel->getById($id);
        if (!$patient) {
            Response::error('Patient not found', 404);
        }

        // Check ownership
        if ((int)$patient['user_id'] !== (int)$userId) {
            Response::error('Forbidden: You do not have access to this patient', 403);
        }

        // Delete patient
        if ($this->patientModel->deleteByIdAndUserId($id, $userId)) {
            Response::success(null, 'Patient deleted successfully');
        } else {
            Response::error('Failed to delete patient', 500);
        }
    }
}