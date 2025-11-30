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
    
    // Find all trainings and update their statuses based on dates
    $stmt = $pdo->prepare("SELECT id, title, user_id, office_code, start_date, end_date, status FROM training_records");
    $stmt->execute();
    $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated_count = 0;
    
    foreach ($trainings as $training) {
        // Determine correct status based on dates
        $start_date = $training['start_date'];
        $end_date = $training['end_date'];
        
        // Automatically determine status based on dates
        if ($end_date < $current_date) {
            $correct_status = 'completed';
        } elseif ($start_date <= $current_date && $end_date >= $current_date) {
            $correct_status = 'ongoing';
        } else {
            $correct_status = 'upcoming';
        }
        
        // Only update if status is different
        if ($training['status'] !== $correct_status) {
            // Update training status
            $update_stmt = $pdo->prepare("UPDATE training_records SET status = ? WHERE id = ?");
            $result = $update_stmt->execute([$correct_status, $training['id']]);
            
            if ($result) {
                $updated_count++;
                
                // If the training is being marked as completed, send notifications
                if ($correct_status === 'completed') {
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
        }
    }
    
    if ($cli_mode) {
        echo "Updated statuses for {$updated_count} trainings\n";
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Updated statuses for {$updated_count} trainings",
            'updated_count' => $updated_count
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