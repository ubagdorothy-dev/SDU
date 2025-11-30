<?php
/**
 * Test script for notification system
 * This script can be used to verify that notifications are working correctly
 */

session_start();
require_once 'db.php';

// Check if user is authenticated as unit director
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = connect_db();
    
    // Get current user info
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_name = $user ? $user['full_name'] : 'Unknown User';
    
    // Get notification statistics
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
    $total_notifications = $total_stmt->fetchColumn();
    
    $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_notifications = $unread_stmt->fetchColumn();
    
    // Get recent notifications
    $recent_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $recent_stmt->execute([$user_id]);
    $recent_notifications = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system statistics
    $staff_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'staff'");
    $staff_count_stmt->execute();
    $total_staff = $staff_count_stmt->fetchColumn();
    
    $training_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records");
    $training_count_stmt->execute();
    $total_trainings = $training_count_stmt->fetchColumn();
    
    $completed_training_stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE status = 'completed'");
    $completed_training_stmt->execute();
    $completed_trainings = $completed_training_stmt->fetchColumn();
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1>Notification System Test</h1>
                <p class="text-muted">Testing notification functionality for Unit Director: <?php echo htmlspecialchars($user_name); ?></p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Notifications</h5>
                                <p class="card-text display-4"><?php echo $total_notifications; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Unread Notifications</h5>
                                <p class="card-text display-4"><?php echo $unread_notifications; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">System Stats</h5>
                                <p class="card-text">
                                    Staff: <?php echo $total_staff; ?><br>
                                    Trainings: <?php echo $total_trainings; ?><br>
                                    Completed: <?php echo $completed_trainings; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Notifications</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_notifications)): ?>
                                    <p class="text-muted">No notifications found.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_notifications as $notification): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                                        <td>
                                                            <?php if ($notification['is_read']): ?>
                                                                <span class="badge bg-success">Read</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Unread</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Test Notification Functions</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary" onclick="testNotification()">Send Test Notification</button>
                                <button class="btn btn-secondary" onclick="markAllRead()">Mark All Read</button>
                                <button class="btn btn-danger" onclick="clearNotifications()">Clear All Notifications</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testNotification() {
            if (confirm('Send a test notification to yourself?')) {
                // In a real implementation, this would call an API endpoint
                alert('In a real system, this would send a test notification. Check the notification system documentation for implementation details.');
            }
        }
        
        function markAllRead() {
            if (confirm('Mark all notifications as read?')) {
                // In a real implementation, this would call an API endpoint
                alert('In a real system, this would mark all notifications as read. Check the notification system documentation for implementation details.');
            }
        }
        
        function clearNotifications() {
            if (confirm('Delete all notifications? This cannot be undone.')) {
                // In a real implementation, this would call an API endpoint
                alert('In a real system, this would delete all notifications. Check the notification system documentation for implementation details.');
            }
        }
    </script>
</body>
</html>