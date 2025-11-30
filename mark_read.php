<?php
/**
 * Mark notifications as read
 */

session_start();
require_once 'db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get notification IDs from POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_ids = $input['ids'] ?? [];
    
    if (empty($notification_ids) || !is_array($notification_ids)) {
        echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
        exit();
    }
    
    // Sanitize IDs
    $ids = array_map('intval', $notification_ids);
    
    // Build placeholders for the IN clause
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    // Update notifications to mark as read, ensuring they belong to the current user
    $sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?";
    $params = array_merge($ids, [$_SESSION['user_id']]);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
    
} catch (Exception $e) {
    error_log("Error in mark_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while marking notifications as read']);
}
?>