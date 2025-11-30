<?php
/**
 * Diagnostic script to check notification issues
 */

require_once 'db.php';

try {
    $pdo = connect_db();
    
    echo "<h1>Notification System Diagnosis</h1>";
    
    // 1. Check users and their roles
    echo "<h2>1. Users and Roles</h2>";
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role FROM users ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
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
    
    // 2. Check specifically for unit directors
    echo "<h2>2. Unit Directors</h2>";
    $directorsStmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE role = 'unit director'");
    $directorsStmt->execute();
    $directors = $directorsStmt->fetchAll();
    
    if (empty($directors)) {
        echo "<p style='color: red;'>No unit directors found with role 'unit director'!</p>";
    } else {
        echo "<p style='color: green;'>Found " . count($directors) . " unit director(s)</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>User ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($directors as $director) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($director['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($director['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($director['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check for office heads
    echo "<h2>3. Office Heads</h2>";
    $headsStmt = $pdo->prepare("SELECT user_id, full_name, email, office_code FROM users WHERE role = 'head'");
    $headsStmt->execute();
    $heads = $headsStmt->fetchAll();
    
    if (empty($heads)) {
        echo "<p style='color: red;'>No office heads found!</p>";
    } else {
        echo "<p style='color: green;'>Found " . count($heads) . " office head(s)</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Office Code</th></tr>";
        foreach ($heads as $head) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($head['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($head['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($head['email']) . "</td>";
            echo "<td>" . htmlspecialchars($head['office_code'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Check recent notifications
    echo "<h2>4. Recent Notifications</h2>";
    $notificationsStmt = $pdo->prepare("SELECT n.*, u.full_name as recipient_name FROM notifications n JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 10");
    $notificationsStmt->execute();
    $notifications = $notificationsStmt->fetchAll();
    
    if (empty($notifications)) {
        echo "<p>No notifications found in the database.</p>";
    } else {
        echo "<p>Found " . count($notifications) . " recent notification(s)</p>";
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
    }
    
    // 5. Test notification creation
    echo "<h2>5. Test Notification Creation</h2>";
    
    // Try to create a test notification for the unit director
    if (!empty($directors)) {
        $testDirector = $directors[0];
        $testStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $result = $testStmt->execute([$testDirector['user_id'], 'Test Notification', 'This is a test notification to verify the system is working.']);
        
        if ($result) {
            echo "<p style='color: green;'>Successfully created test notification for " . htmlspecialchars($testDirector['full_name']) . "</p>";
            
            // Check if it was actually created
            $checkStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND title = 'Test Notification' ORDER BY created_at DESC LIMIT 1");
            $checkStmt->execute([$testDirector['user_id']]);
            $testNotification = $checkStmt->fetch();
            
            if ($testNotification) {
                echo "<p style='color: green;'>Test notification confirmed in database</p>";
            } else {
                echo "<p style='color: red;'>Test notification not found in database</p>";
            }
        } else {
            echo "<p style='color: red;'>Failed to create test notification</p>";
        }
    } else {
        echo "<p>Cannot test notification creation - no unit directors found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>