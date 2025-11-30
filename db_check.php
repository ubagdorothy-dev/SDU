<?php
// Simple database check script
echo "<h1>Database Check</h1>";

// Include the database connection
require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Check users and their roles
    echo "<h2>Users in Database:</h2>";
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role FROM users ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check notifications
    echo "<h2>Recent Notifications:</h2>";
    $stmt = $pdo->prepare("SELECT n.id, n.title, n.message, n.is_read, n.created_at, u.full_name as recipient FROM notifications n JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<p>No notifications found</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Recipient</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($n['id']) . "</td>";
            echo "<td>" . htmlspecialchars($n['recipient']) . "</td>";
            echo "<td>" . htmlspecialchars($n['title']) . "</td>";
            echo "<td>" . htmlspecialchars($n['message']) . "</td>";
            echo "<td>" . ($n['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($n['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>