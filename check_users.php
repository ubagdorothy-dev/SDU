<?php
/**
 * Check users and their roles in the database
 */

require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Get all users and their roles
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role, office_code FROM users ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>All Users</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Office Code</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['office_code'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check specifically for unit directors
    echo "<h2>Unit Directors</h2>";
    $directorsStmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE role IN ('unit_director', 'unit director')");
    $directorsStmt->execute();
    $directors = $directorsStmt->fetchAll();
    
    if (empty($directors)) {
        echo "<p>No unit directors found.</p>";
    } else {
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
    
    // Check specifically for office heads
    echo "<h2>Office Heads</h2>";
    $headsStmt = $pdo->prepare("SELECT user_id, full_name, email, office_code FROM users WHERE role = 'head'");
    $headsStmt->execute();
    $heads = $headsStmt->fetchAll();
    
    if (empty($heads)) {
        echo "<p>No office heads found.</p>";
    } else {
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
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>