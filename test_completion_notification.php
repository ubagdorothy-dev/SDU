<?php
/**
 * Test script for the completion notification feature
 */

require_once 'db.php';

try {
    $pdo = connect_db();
    
    // Create a test training record
    $test_title = "Test Training Notification - " . date('Y-m-d H:i:s');
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 day'));
    
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
    $ins->execute([$user_id, $test_title, 'Test training for completion notification', $start_date, $end_date, 'upcoming', $office_code]);
    
    $training_id = $pdo->lastInsertId();
    echo "Created training record with ID: $training_id\n";
    
    // Verify it was created with the correct status
    $check = $pdo->prepare("SELECT id, title, status FROM training_records WHERE id = ?");
    $check->execute([$training_id]);
    $training = $check->fetch(PDO::FETCH_FETCH_ASSOC);
    
    if ($training) {
        echo "Training created successfully:\n";
        echo "  ID: {$training['id']}\n";
        echo "  Title: {$training['title']}\n";
        echo "  Status: {$training['status']}\n";
    } else {
        echo "Failed to create training record.\n";
        exit(1);
    }
    
    echo "\nTesting manual completion via update_training_status.php...\n";
    
    // Simulate the completion process by directly calling the update function
    // We'll simulate the GET parameters that would be passed
    $_GET['id'] = $training_id;
    $_GET['status'] = 'completed';
    
    // Start output buffering to capture the response
    ob_start();
    
    // Set up session variables as if a user was logged in
    session_start();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = 'staff';
    
    // Include the update script
    include 'update_training_status.php';
    
    $response = ob_get_clean();
    
    echo "Response from update_training_status.php:\n";
    echo $response . "\n";
    
    // Check if the training status was updated
    $check2 = $pdo->prepare("SELECT id, title, status FROM training_records WHERE id = ?");
    $check2->execute([$training_id]);
    $updated_training = $check2->fetch(PDO::FETCH_ASSOC);
    
    if ($updated_training && $updated_training['status'] === 'completed') {
        echo "SUCCESS: Training was manually marked as completed!\n";
        echo "  ID: {$updated_training['id']}\n";
        echo "  Title: {$updated_training['title']}\n";
        echo "  Status: {$updated_training['status']}\n";
    } else {
        echo "FAILED: Training was not manually marked as completed.\n";
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
        
        // Show the actual notifications
        $notif_list = $pdo->prepare("SELECT user_id, title, message FROM notifications WHERE title = 'Staff Training Completed' ORDER BY id DESC LIMIT 5");
        $notif_list->execute();
        $notifications = $notif_list->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent notifications:\n";
        foreach ($notifications as $notification) {
            echo "  To User ID: {$notification['user_id']} - {$notification['title']}: {$notification['message']}\n";
        }
    } else {
        echo "INFO: No notifications found for Unit Heads.\n";
    }
    
    // Clean up test data
    echo "\nCleaning up test data...\n";
    $del_notif = $pdo->prepare("DELETE FROM notifications WHERE title = 'Staff Training Completed' AND message LIKE '%Test Training Notification%'");
    $del_notif->execute();
    
    $del_training = $pdo->prepare("DELETE FROM training_records WHERE id = ?");
    $del_training->execute([$training_id]);
    
    echo "Test completed and cleaned up.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>