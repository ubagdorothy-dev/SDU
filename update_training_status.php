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
    
    // Get the training ID from query params or POST
    $training_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);
    
    if (!$training_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameter (id)']);
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
    
    // Get current training details to automatically determine status
    $training_details = $pdo->prepare("SELECT start_date, end_date FROM training_records WHERE id = ?");
    $training_details->execute([$training_id]);
    $details = $training_details->fetch(PDO::FETCH_ASSOC);
    
    if ($details) {
        $start_date = $details['start_date'];
        $end_date = $details['end_date'];
        $current_date = date('Y-m-d');
        
        // Automatically determine status based on dates
        if ($end_date < $current_date) {
            $status = 'completed';
        } elseif ($start_date <= $current_date && $end_date >= $current_date) {
            $status = 'ongoing';
        } else {
            $status = 'upcoming';
        }
    }
    
    // Update the training status
    $update = $pdo->prepare("UPDATE training_records SET status = ? WHERE id = ?");
    $update->execute([$status, $training_id]);
    
    // If the training is being marked as completed, send notifications to Unit Head
    if ($status === 'completed') {
        // Get training details
        $training_stmt = $pdo->prepare("SELECT title, user_id, office_code FROM training_records WHERE id = ?");
        $training_stmt->execute([$training_id]);
        $training_info = $training_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($training_info) {
            $title = $training_info['title'];
            $staff_user_id = $training_info['user_id'];
            $office_code = $training_info['office_code'];
            
            // Get staff name
            $staff_stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $staff_stmt->execute([$staff_user_id]);
            $staff_info = $staff_stmt->fetch(PDO::FETCH_ASSOC);
            $staff_name = $staff_info ? $staff_info['full_name'] : 'Unknown Staff';
            
            // Create notification message
            $message = "Staff member {$staff_name} has completed training: {$title}";
            
            // Notify unit directors (role 'unit director')
            $nd = $pdo->prepare("SELECT user_id FROM users WHERE role = 'unit director'");
            $nd->execute();
            $unitDirectors = $nd->fetchAll(PDO::FETCH_COLUMN);
            foreach ($unitDirectors as $ud) {
                $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $insn->execute([$ud, 'Staff Training Completed', $message]);
            }
            
            // Notify office heads within same office_code
            if ($office_code) {
                $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
                $nh->execute([$office_code]);
                $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
                foreach ($heads as $h) {
                    $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                    $insn->execute([$h, 'Staff Training Completed', $message]);
                }
            }
        }
    }
    
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
