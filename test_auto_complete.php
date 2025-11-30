<?php
/**
 * Test script for the auto completion feature
 */

require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Create a test training record with an end date in the past
    $test_title = "Test Training - " . date('Y-m-d H:i:s');
    $past_date = date('Y-m-d', strtotime('-2 days'));
    $future_date = date('Y-m-d', strtotime('-1 day'));
    
    // Get a test user ID (first staff user)
    $user_stmt = $pdo->prepare("SELECT user_id, office_code FROM users WHERE role = 'staff' LIMIT 1");
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "No test user found. Please create a staff user first.\n";
        exit(1);
    }
    
    $user_id = $user['user_id'];
    $office_code = $user['office_code'];
    
    echo "Creating test training record...\n";
    echo "User ID: $user_id\n";
    echo "Office Code: $office_code\n";
    
    // Insert test training record
    $ins = $pdo->prepare("INSERT INTO training_records (user_id, title, description, start_date, end_date, status, office_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$user_id, $test_title, 'Test training for auto-completion', $past_date, $future_date, 'upcoming', $office_code]);
    
    $training_id = $pdo->lastInsertId();
    echo "Created training record with ID: $training_id\n";
    
    // Verify it was created with the correct status
    $check = $pdo->prepare("SELECT id, title, status FROM training_records WHERE id = ?");
    $check->execute([$training_id]);
    $training = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($training) {
        echo "Training created successfully:\n";
        echo "  ID: {$training['id']}\n";
        echo "  Title: {$training['title']}\n";
        echo "  Status: {$training['status']}\n";
    } else {
        echo "Failed to create training record.\n";
        exit(1);
    }
    
    echo "\nRunning auto-completion script...\n";
    
    // Include the auto-completion script
    include 'auto_complete_trainings.php';
    
    echo "\nChecking if training was auto-completed...\n";
    
    // Check if the training status was updated
    $check2 = $pdo->prepare("SELECT id, title, status FROM training_records WHERE id = ?");
    $check2->execute([$training_id]);
    $updated_training = $check2->fetch(PDO::FETCH_ASSOC);
    
    if ($updated_training && $updated_training['status'] === 'completed') {
        echo "SUCCESS: Training was automatically marked as completed!\n";
        echo "  ID: {$updated_training['id']}\n";
        echo "  Title: {$updated_training['title']}\n";
        echo "  Status: {$updated_training['status']}\n";
    } else {
        echo "FAILED: Training was not automatically marked as completed.\n";
        if ($updated_training) {
            echo "  Current status: {$updated_training['status']}\n";
        }
    }
    
    // Check if notifications were created
    echo "\nChecking for notifications...\n";
    $notif_check = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE title = 'Staff Training Completed'");
    $notif_check->execute();
    $notif_count = $notif_check->fetch(PDO::FETCH_ASSOC);
    
    if ($notif_count && $notif_count['count'] > 0) {
        echo "SUCCESS: {$notif_count['count']} notifications were created for Unit Heads.\n";
    } else {
        echo "INFO: No notifications found for Unit Heads.\n";
    }
    
    // Clean up test data
    echo "\nCleaning up test data...\n";
    $del_notif = $pdo->prepare("DELETE FROM notifications WHERE title = 'Staff Training Completed' AND message LIKE '%Test Training%'");
    $del_notif->execute();
    
    $del_training = $pdo->prepare("DELETE FROM training_records WHERE id = ?");
    $del_training->execute([$training_id]);
    
    echo "Test completed and cleaned up.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>