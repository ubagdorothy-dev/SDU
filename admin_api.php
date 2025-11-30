<?php
/**
 * Admin API - Handle admin operations
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? ($_POST['action'] ?? 'view');
    
    if ($action === 'get_staff_list') {
        // Get all staff with their details
        $stmt = $pdo->prepare("SELECT u.user_id, u.full_name, u.email, u.role, u.office_code, s.position, s.program, s.job_function, s.employment_status, s.degree_attained FROM users u LEFT JOIN staff_details s ON u.user_id = s.user_id WHERE u.role IN ('staff', 'head') ORDER BY u.full_name");
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'staff' => $staff
        ]);
        exit();
    }
    
    if ($action === 'get_training_stats') {
        // Get training statistics
        $stats = [];
        
        // Total trainings
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM training_records");
        $stmt->execute();
        $stats['total_trainings'] = $stmt->fetchColumn();
        
        // Completed trainings
        $stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM training_records WHERE status = 'completed'");
        $stmt->execute();
        $stats['completed_trainings'] = $stmt->fetchColumn();
        
        // Ongoing trainings
        $stmt = $pdo->prepare("SELECT COUNT(*) as ongoing FROM training_records WHERE status = 'ongoing'");
        $stmt->execute();
        $stats['ongoing_trainings'] = $stmt->fetchColumn();
        
        // Upcoming trainings
        $stmt = $pdo->prepare("SELECT COUNT(*) as upcoming FROM training_records WHERE status = 'upcoming'");
        $stmt->execute();
        $stats['upcoming_trainings'] = $stmt->fetchColumn();
        
        // Trainings by office
        $stmt = $pdo->prepare("SELECT o.name as office, COUNT(tr.id) as count FROM training_records tr JOIN users u ON tr.user_id = u.user_id JOIN offices o ON u.office_code = o.code GROUP BY o.name ORDER BY count DESC");
        $stmt->execute();
        $stats['trainings_by_office'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        exit();
    }
    
    if ($action === 'get_office_stats') {
        // Get office-wise statistics
        $stats = [];
        
        // Get all offices
        $stmt = $pdo->prepare("SELECT code, name FROM offices");
        $stmt->execute();
        $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no offices found, return empty stats
        if (empty($offices)) {
            echo json_encode([
                'success' => true,
                'stats' => []
            ]);
            exit();
        }
        
        foreach ($offices as $office) {
            // Get total staff in office
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE COALESCE(office_code, '') = COALESCE(?, '') AND role IN ('staff', 'head')");
            $stmt->execute([$office['code']]);
            $total_staff = $stmt->fetchColumn();
            
            // Get completed trainings for office
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE COALESCE(u.office_code, '') = COALESCE(?, '') AND tr.status = 'completed'");
            $stmt->execute([$office['code']]);
            $completed_trainings = $stmt->fetchColumn();
            
            $stats[] = [
                'office_name' => $office['name'],
                'office_code' => $office['code'],
                'total_staff' => $total_staff,
                'completed_trainings' => $completed_trainings
            ];
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        exit();
    }
    
    if ($action === 'get_recent_trainings') {
        // Get recent training completions with user details
        $stmt = $pdo->prepare("SELECT tr.title, tr.created_at as completion_date, u.full_name, COALESCE(u.office_code, 'Unassigned') as office_code FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE tr.status = 'completed' ORDER BY tr.created_at DESC LIMIT 10");
        $stmt->execute();
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($trainings as &$training) {
            $training['completion_date'] = date('M j, Y', strtotime($training['completion_date']));
        }
        
        echo json_encode([
            'success' => true,
            'trainings' => $trainings
        ]);
        exit();
    }
    
    if ($action === 'get_notifications') {
        // Get recent notifications for admin
        $stmt = $pdo->prepare("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format timestamps
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = time_ago($notification['created_at']);
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        exit();
    }
    
    if ($action === 'mark_notification_read') {
        $notification_id = intval($_POST['id'] ?? 0);
        
        if (!$notification_id) {
            echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
            exit();
        }
        
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
        }
        exit();
    }
    
    if ($action === 'mark_notifications_read') {
        // Get notification IDs from POST data
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_ids = $input['ids'] ?? [];
        
        if (empty($notification_ids) || !is_array($notification_ids)) {
            echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
            exit();
        }
        
        // Sanitize IDs
        $ids = array_map('intval', $notification_ids);
        
        // Build placeholders for the IN clause
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // Update notifications to mark as read, ensuring they belong to the current user
        $sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?";
        $params = array_merge($ids, [$user_id]);
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
        }
        exit();
    }
    
    if ($action === 'delete_notification') {
        $notification_id = intval($_POST['id'] ?? 0);
        
        if (!$notification_id) {
            echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notification deleted']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete notification']);
        }
        exit();
    }
    
    if ($action === 'delete_notifications') {
        // Get notification IDs from POST data
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_ids = $input['ids'] ?? [];
        
        if (empty($notification_ids) || !is_array($notification_ids)) {
            echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
            exit();
        }
        
        // Sanitize IDs
        $ids = array_map('intval', $notification_ids);
        
        // Build placeholders for the IN clause
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // Delete notifications, ensuring they belong to the current user
        $sql = "DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ?";
        $params = array_merge($ids, [$user_id]);
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notifications deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete notifications']);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("Error in admin_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}

// Helper function to calculate time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>