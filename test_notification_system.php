<?php
// Test notification creation
require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Check if we have unit directors
    $stmt = $pdo->prepare("SELECT user_id, full_name, role FROM users WHERE role = 'unit director'");
    $stmt->execute();
    $unitDirectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Unit Directors Found:</h2>";
    if (empty($unitDirectors)) {
        echo "<p>No unit directors found with role 'unit director'</p>";
    } else {
        echo "<ul>";
        foreach ($unitDirectors as $ud) {
            echo "<li>User ID: " . htmlspecialchars($ud['user_id']) . " - Name: " . htmlspecialchars($ud['full_name']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Check if we have office heads
    $stmt = $pdo->prepare("SELECT user_id, full_name, role, office_code FROM users WHERE role = 'head'");
    $stmt->execute();
    $heads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Office Heads Found:</h2>";
    if (empty($heads)) {
        echo "<p>No office heads found</p>";
    } else {
        echo "<ul>";
        foreach ($heads as $h) {
            echo "<li>User ID: " . htmlspecialchars($h['user_id']) . " - Name: " . htmlspecialchars($h['full_name']) . " - Office: " . htmlspecialchars($h['office_code']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Check existing notifications
    $stmt = $pdo->prepare("SELECT n.*, u.full_name as recipient_name FROM notifications n JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Recent Notifications:</h2>";
    if (empty($notifications)) {
        echo "<p>No notifications found</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Recipient</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($n['id']) . "</td>";
            echo "<td>" . htmlspecialchars($n['recipient_name']) . "</td>";
            echo "<td>" . htmlspecialchars($n['title']) . "</td>";
            echo "<td>" . htmlspecialchars($n['message']) . "</td>";
            echo "<td>" . ($n['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($n['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>