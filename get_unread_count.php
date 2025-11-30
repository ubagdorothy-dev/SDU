<?php
/**
 * Get unread notifications count for current user
 */

session_start();
require_once 'db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get unread notifications count for current user
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)$result['count']]);
    
} catch (Exception $e) {
    error_log("Error in get_unread_count.php: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}
?>