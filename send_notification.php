<?php
/**
 * Send notification API endpoint
 * Used by admin dashboard to send broadcast notifications
 */

session_start();
require_once 'db.php';

// Check if user is authenticated and is an admin/director
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get POST data
    $audience = $_POST['audience'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate input
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }
    
    // Prepare notification title
    $title = !empty($subject) ? $subject : 'System Announcement';
    
    // Get sender name
    $sender_stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $sender_stmt->execute([$_SESSION['user_id']]);
    $sender = $sender_stmt->fetch(PDO::FETCH_ASSOC);
    $sender_name = $sender ? $sender['full_name'] : 'System Administrator';
    
    // Add sender info to message
    $full_message = "From: {$sender_name}\n\n{$message}";
    
    // Get recipient user IDs based on audience
    $recipient_ids = [];
    
    switch ($audience) {
        case 'all':
            // Get all users except the sender
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
            $stmt->execute([$_SESSION['user_id']]);
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $recipient_ids = $recipients;
            break;
            
        case 'staff':
            // Get all staff users
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'staff'");
            $stmt->execute();
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $recipient_ids = $recipients;
            break;
            
        case 'heads':
            // Get all office heads
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head'");
            $stmt->execute();
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $recipient_ids = $recipients;
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid audience selection']);
            exit();
    }
    
    // Insert notifications for each recipient
    $inserted_count = 0;
    foreach ($recipient_ids as $user_id) {
        $insert_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $result = $insert_stmt->execute([$user_id, $title, $full_message]);
        if ($result) {
            $inserted_count++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Notification sent successfully to {$inserted_count} users",
        'recipient_count' => $inserted_count
    ]);
    
} catch (Exception $e) {
    error_log("Error in send_notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the notification']);
}
?>