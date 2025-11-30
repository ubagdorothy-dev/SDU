<?php
/**
 * Profile API - Handle staff profile operations
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// Check authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? ($_POST['action'] ?? 'view');
    
    if ($action === 'view') {
        // Get user profile data
        $user_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        $details_stmt = $pdo->prepare("SELECT position, program, job_function, employment_status, degree_attained, degree_other FROM staff_details WHERE user_id = ?");
        $details_stmt->execute([$user_id]);
        $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$details) {
            // Create empty staff details record if it doesn't exist
            $insert_stmt = $pdo->prepare("INSERT INTO staff_details (user_id, position, program, job_function, employment_status, degree_attained, degree_other) VALUES (?, '', '', '', '', '', '')");
            $insert_stmt->execute([$user_id]);
            $details = [
                'position' => '',
                'program' => '',
                'job_function' => '',
                'employment_status' => '',
                'degree_attained' => '',
                'degree_other' => ''
            ];
        }
        
        echo json_encode([
            'success' => true,
            'profile' => array_merge($user, $details)
        ]);
        exit();
    }
    
    if ($action === 'update') {
        // Update profile data
        $position = $_POST['position'] ?? '';
        $program = $_POST['program'] ?? '';
        $job_function = $_POST['job_function'] ?? '';
        $employment_status = $_POST['employment_status'] ?? '';
        $degree_attained = $_POST['degree_attained'] ?? '';
        $degree_other = $_POST['degree_other'] ?? '';
        
        // Update staff details
        $update_stmt = $pdo->prepare("UPDATE staff_details SET position = ?, program = ?, job_function = ?, employment_status = ?, degree_attained = ?, degree_other = ? WHERE user_id = ?");
        $result = $update_stmt->execute([
            $position, $program, $job_function, $employment_status, $degree_attained, $degree_other, $user_id
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("Error in profile_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}
?>