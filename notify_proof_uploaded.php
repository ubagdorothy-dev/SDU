<?php
/**
 * Notify proof uploaded API endpoint
 * Handles notifications when staff upload proof of training completion
 */

session_start();
require_once 'db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get POST data
    $training_id = intval($_POST['training_id'] ?? 0);
    
    // Validate input
    if (!$training_id) {
        echo json_encode(['success' => false, 'message' => 'Training ID is required']);
        exit();
    }
    
    // Get training details
    $stmt = $pdo->prepare("SELECT tr.title, tr.user_id, tr.office_code, u.full_name FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE tr.id = ?");
    $stmt->execute([$training_id]);
    $training = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$training) {
        echo json_encode(['success' => false, 'message' => 'Training not found']);
        exit();
    }
    
    $title = $training['title'];
    $staff_name = $training['full_name'];
    $office_code = $training['office_code'];
    $staff_user_id = $training['user_id'];
    
    // Create notification message
    $message = "Proof of completion uploaded by {$staff_name} for training: {$title}";
    $notification_title = "Proof of Completion Uploaded";
    
    // Notify unit directors (role 'unit director')
    $nd = $pdo->prepare("SELECT user_id FROM users WHERE role = 'unit director'");
    $nd->execute();
    $unitDirectors = $nd->fetchAll(PDO::FETCH_COLUMN);
    
    $notification_count = 0;
    
    foreach ($unitDirectors as $ud) {
        $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $result = $insn->execute([$ud, $notification_title, $message]);
        if ($result) {
            $notification_count++;
        }
    }
    
    // Notify office heads within same office_code
    if ($office_code) {
        $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
        $nh->execute([$office_code]);
        $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($heads as $h) {
            $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $result = $insn->execute([$h, $notification_title, $message]);
            if ($result) {
                $notification_count++;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Notification sent successfully to {$notification_count} users",
        'notification_count' => $notification_count
    ]);
    
} catch (Exception $e) {
    error_log("Error in notify_proof_uploaded.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the notification']);
}
?>