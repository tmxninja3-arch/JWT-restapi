<?php
namespace App\Controllers;

use App\Models\Patient;
use App\Helpers\Response;

class PatientController {
    private $patientModel;
    
    public function __construct() {
        $this->patientModel = new Patient();
    }
    
    public function index($request) {
        $patients = $this->patientModel->getAll();
        Response::success($patients, 'Patients retrieved successfully');
    }
    
    public function show($request, $id) {
        $patient = $this->patientModel->getById($id);
        
        if (!$patient) {
            Response::error('Patient not found', 404);
        }
        
        Response::success($patient, 'Patient retrieved successfully');
    }
    
    public function store($request) {
        $data = $request['body'];
        
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
        
        // Create patient
        $patientId = $this->patientModel->create($data);
        
        if ($patientId) {
            $patient = $this->patientModel->getById($patientId);
            Response::success($patient, 'Patient created successfully', 201);
        } else {
            Response::error('Failed to create patient', 500);
        }
    }
    
    public function update($request, $id) {
        $data = $request['body'];
        
        // Check if patient exists
        $patient = $this->patientModel->getById($id);
        if (!$patient) {
            Response::error('Patient not found', 404);
        }
        
        // Validate required fields
        $required = ['name', 'age', 'gender', 'phone', 'address'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Response::error("Field '$field' is required", 400);
            }
        }
        
        // Update patient
        if ($this->patientModel->update($id, $data)) {
            $updatedPatient = $this->patientModel->getById($id);
            Response::success($updatedPatient, 'Patient updated successfully');
        } else {
            Response::error('Failed to update patient', 500);
        }
    }
    
    public function destroy($request, $id) {
        // Check if patient exists
        $patient = $this->patientModel->getById($id);
        if (!$patient) {
            Response::error('Patient not found', 404);
        }
        
        // Delete patient
        if ($this->patientModel->delete($id)) {
            Response::success(null, 'Patient deleted successfully');
        } else {
            Response::error('Failed to delete patient', 500);
        }
    }
}