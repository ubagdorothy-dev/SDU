<?php
/**
 * Check notifications in the database
 */

require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Get the latest 10 notifications
    $stmt = $pdo->prepare("SELECT n.*, u.full_name as recipient_name FROM notifications n JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    echo "<h2>Latest 10 Notifications</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Recipient</th><th>Title</th><th>Message</th><th>Read</th><th>Created At</th></tr>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($notification['id']) . "</td>";
        echo "<td>" . htmlspecialchars($notification['recipient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
        echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
        echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($notification['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Count total notifications
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications");
    $countStmt->execute();
    $count = $countStmt->fetch();
    
    echo "<p>Total notifications: " . $count['total'] . "</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>