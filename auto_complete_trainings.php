<?php
/**
 * Auto-complete trainings script
 * This script should be run periodically (e.g., daily) to automatically mark trainings as completed
 * when their end date has passed
 */

// This script can be run from command line or web request
$cli_mode = (php_sapi_name() === 'cli');

if ($cli_mode) {
    // When running from CLI, include the db connection file directly
    if (file_exists(__DIR__ . '/db.php')) {
        require_once __DIR__ . '/db.php';
    } else {
        die("Database connection file not found\n");
    }
} else {
    // When running from web, start session and include db connection
    session_start();
    require_once 'db.php';
    
    // Check if user is authorized (unit director or admin)
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
        http_response_code(401);
        die("Unauthorized access");
    }
}

try {
    $pdo = connect_db();
    
    // Get current date
    $current_date = date('Y-m-d');
    
    // Find trainings that have ended but are not marked as completed
    $stmt = $pdo->prepare("SELECT id, title, user_id, office_code FROM training_records WHERE end_date < ? AND status != 'completed'");
    $stmt->execute([$current_date]);
    $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $completed_count = 0;
    
    foreach ($trainings as $training) {
        // Update training status to completed
        $update_stmt = $pdo->prepare("UPDATE training_records SET status = 'completed' WHERE id = ?");
        $result = $update_stmt->execute([$training['id']]);
        
        if ($result) {
            $completed_count++;
            
            // Send notifications to Unit Head and Directors
            $title = $training['title'];
            $staff_user_id = $training['user_id'];
            $office_code = $training['office_code'];
            
            // Get staff name
            $staff_stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $staff_stmt->execute([$staff_user_id]);
            $staff_info = $staff_stmt->fetch(PDO::FETCH_ASSOC);
            $staff_name = $staff_info ? $staff_info['full_name'] : 'Unknown Staff';
            
            // Create notification message
            $message = "Staff member {$staff_name} has completed training: {$title}";
            
            // Notify unit directors (role 'unit director')
            $nd = $pdo->prepare("SELECT user_id FROM users WHERE role = 'unit director'");
            $nd->execute();
            $unitDirectors = $nd->fetchAll(PDO::FETCH_COLUMN);
            foreach ($unitDirectors as $ud) {
                $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $insn->execute([$ud, 'Staff Training Completed', $message]);
            }
            
            // Notify office heads within same office_code
            if ($office_code) {
                $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
                $nh->execute([$office_code]);
                $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
                foreach ($heads as $h) {
                    $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                    $insn->execute([$h, 'Staff Training Completed', $message]);
                }
            }
        }
    }
    
    if ($cli_mode) {
        echo "Auto-completed {$completed_count} trainings\n";
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Auto-completed {$completed_count} trainings",
            'completed_count' => $completed_count
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in auto_complete_trainings.php: " . $e->getMessage());
    if ($cli_mode) {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'An error occurred while auto-completing trainings']);
    }
}
?>