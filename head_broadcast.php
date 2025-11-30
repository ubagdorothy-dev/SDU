<?php
/**
 * Head Broadcast API - Allow office heads to send notifications to their staff
 */

session_start();
require_once 'db.php';

// Check if user is authenticated and is an office head
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get current user's office
    $stmt = $pdo->prepare("SELECT office_code, full_name FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || empty($user['office_code'])) {
        echo json_encode(['success' => false, 'message' => 'You must be assigned to an office to send notifications']);
        exit();
    }
    
    $office_code = $user['office_code'];
    $sender_name = $user['full_name'] ?? 'Office Head';
    
    // Get POST data
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate input
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }
    
    // Prepare notification title
    $title = !empty($subject) ? $subject : 'Message from Office Head';
    
    // Add sender info to message
    $full_message = "From: {$sender_name} (Office Head)\n\n{$message}";
    
    // Get all staff members in the same office
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'staff' AND office_code = ?");
    $stmt->execute([$office_code]);
    $staff_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($staff_members)) {
        echo json_encode(['success' => false, 'message' => 'No staff members found in your office']);
        exit();
    }
    
    // Insert notifications for each staff member
    $inserted_count = 0;
    foreach ($staff_members as $user_id) {
        $insert_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $result = $insert_stmt->execute([$user_id, $title, $full_message]);
        if ($result) {
            $inserted_count++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Notification sent successfully to {$inserted_count} staff members",
        'recipient_count' => $inserted_count
    ]);
    
} catch (Exception $e) {
    error_log("Error in head_broadcast.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the notification']);
}
?>