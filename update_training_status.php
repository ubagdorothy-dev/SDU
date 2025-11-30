<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get the training ID and desired status from query params or POST
    $training_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);
    $status = isset($_GET['status']) ? $_GET['status'] : (isset($_POST['status']) ? $_POST['status'] : null);
    
    if (!$training_id || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters (id, status)']);
        exit();
    }
    
    // Validate status is one of allowed values
    $allowed_statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status value']);
        exit();
    }
    
    // Get the training record to check if it exists
    $check = $pdo->prepare("SELECT user_id FROM training_records WHERE id = ?");
    $check->execute([$training_id]);
    $training = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$training) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Training record not found']);
        exit();
    }
    
    // Authorization check: only the staff, their head, or a unit director can update
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? '';
    $is_owner = ($user_id == $training['user_id']);
    $is_director = ($user_role === 'unit director');
    
    // If not the owner or director, check if user is the head of the staff's office
    $is_head_of_office = false;
    if ($user_role === 'head' && !$is_owner) {
        $head_check = $pdo->prepare("SELECT office_code FROM users WHERE user_id = ?");
        $head_check->execute([$user_id]);
        $head_data = $head_check->fetch(PDO::FETCH_ASSOC);
        
        $staff_check = $pdo->prepare("SELECT office_code FROM users WHERE user_id = ?");
        $staff_check->execute([$training['user_id']]);
        $staff_data = $staff_check->fetch(PDO::FETCH_ASSOC);
        
        $is_head_of_office = ($head_data && $staff_data && $head_data['office_code'] === $staff_data['office_code']);
    }
    
    if (!($is_owner || $is_director || $is_head_of_office)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden: You do not have permission to update this training']);
        exit();
    }
    
    // Update the training status
    $update = $pdo->prepare("UPDATE training_records SET status = ? WHERE id = ?");
    $update->execute([$status, $training_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Training status updated successfully',
        'training_id' => $training_id,
        'new_status' => $status
    ]);
    exit();
    
} catch (PDOException $e) {
    error_log("Database error in update_training_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit();
} catch (Exception $e) {
    error_log("Error in update_training_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
    exit();
}
?>
