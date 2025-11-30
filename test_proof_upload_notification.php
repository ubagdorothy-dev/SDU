<?php
/**
 * Test script to simulate proof upload notification
 */

session_start();
require_once 'db.php';

// Simulate a user session (normally this would come from login)
// For testing purposes, we'll use the unit director from the sample data
$_SESSION['user_id'] = 1; // SDU Director user_id from sample data
$_SESSION['role'] = 'unit director';

try {
    $pdo = connect_db();
    
    // Create a test training record if one doesn't exist
    $stmt = $pdo->prepare("SELECT id FROM training_records WHERE user_id = 1 LIMIT 1");
    $stmt->execute();
    $training = $stmt->fetch();
    
    $training_id = null;
    if (!$training) {
        // Create a test training record
        $insertStmt = $pdo->prepare("INSERT INTO training_records (user_id, title, description, start_date, end_date, status, venue, office_code) VALUES (?, 'Test Training', 'Test Description', '2025-12-01', '2025-12-05', 'completed', 'Test Venue', 'ACCA')");
        $insertStmt->execute([1]);
        $training_id = $pdo->lastInsertId();
    } else {
        $training_id = $training['id'];
    }
    
    echo "Using training ID: " . $training_id . "<br>";
    
    // Simulate proof upload notification
    $stmt = $pdo->prepare("SELECT tr.office_code, tr.title, u.full_name FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE tr.id = ?");
    $stmt->execute([$training_id]);
    $tr = $stmt->fetch(PDO::FETCH_ASSOC);
    $office_code = $tr['office_code'] ?? null;
    $title = $tr['title'] ?? 'Training';
    $staff_name = $tr['full_name'] ?? 'Unknown Staff';
    
    $message = "Proof of completion uploaded by {$staff_name} for: $title";
    
    echo "Notification message: " . $message . "<br>";
    
    // Notify unit directors (role 'unit director')
    $nd = $pdo->prepare("SELECT user_id FROM users WHERE role = 'unit director'");
    $nd->execute();
    $unitDirectors = $nd->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($unitDirectors) . " unit directors<br>";
    
    foreach ($unitDirectors as $ud) {
        echo "Sending notification to user ID: " . $ud . "<br>";
        $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $insn->execute([$ud, 'Proof Uploaded', $message]);
        echo "Notification inserted with ID: " . $pdo->lastInsertId() . "<br>";
    }
    
    // Notify office heads within same office_code
    if ($office_code) {
        $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
        $nh->execute([$office_code]);
        $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Found " . count($heads) . " office heads in office " . $office_code . "<br>";
        
        foreach ($heads as $h) {
            echo "Sending notification to office head user ID: " . $h . "<br>";
            $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $insn->execute([$h, 'Proof Uploaded', $message]);
            echo "Notification inserted with ID: " . $pdo->lastInsertId() . "<br>";
        }
    }
    
    echo "<h3>Test completed successfully!</h3>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>