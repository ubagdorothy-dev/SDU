<?php
/**
 * Delete notifications
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
    
    // Delete notifications, ensuring they belong to the current user
    $sql = "DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ?";
    $params = array_merge($ids, [$_SESSION['user_id']]);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notifications deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notifications']);
    }
    
} catch (Exception $e) {
    error_log("Error in delete_notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting notifications']);
}
?>