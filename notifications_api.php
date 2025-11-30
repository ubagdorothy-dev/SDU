<?php
/**
 * Notifications API - Fetch notifications for current user
 */

session_start();
require_once 'db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    
    // Get notifications for current user, ordered by creation date (newest first)
    $stmt = $pdo->prepare("
        SELECT id, title, message, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if request wants HTML response (for office head dashboard)
    $accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    if (strpos($accept_header, 'text/html') !== false || isset($_GET['format']) && $_GET['format'] === 'html') {
        // Return HTML format for office head dashboard
        renderNotificationsHTML($notifications);
        exit();
    }
    
    // Format the response as JSON (default)
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $formatted_notifications[] = [
            'id' => (int)$notification['id'],
            'title' => $notification['title'],
            'message' => nl2br(htmlspecialchars($notification['message'])),
            'is_read' => (bool)$notification['is_read'],
            'created_at' => $notification['created_at'],
            'time_ago' => time_ago($notification['created_at'])
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications
    ]);
    
} catch (Exception $e) {
    error_log("Error in notifications_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching notifications']);
}

/**
 * Convert datetime to human-readable "time ago" format
 */
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
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Render notifications as HTML for office head dashboard
 */
function renderNotificationsHTML($notifications) {
    header('Content-Type: text/html');
    
    if (empty($notifications)) {
        echo '<div class="text-center py-5">
                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                <h5>No notifications</h5>
                <p class="text-muted">You don\'t have any notifications at the moment.</p>
              </div>';
        return;
    }
    
    echo '<div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <button id="markAllReadBtn" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-check-double me-1"></i>Mark All Read
                </button>
                <button id="deleteAllBtn" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i>Delete All
                </button>
            </div>
            <div>
                <span class="text-muted small">' . count($notifications) . ' notifications</span>
            </div>
          </div>';
    
    echo '<div class="list-group">';
    
    foreach ($notifications as $notification) {
        $isUnreadClass = !$notification['is_read'] ? 'list-group-item-warning unread' : '';
        $unreadIndicator = !$notification['is_read'] ? '<span class="badge bg-warning me-2">NEW</span>' : '';
        $timeAgo = time_ago($notification['created_at']);
        
        echo '<div class="list-group-item ' . $isUnreadClass . '" data-id="' . (int)$notification['id'] . '">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">' . $unreadIndicator . htmlspecialchars($notification['title']) . '</h6>
                    <small class="text-muted">' . $timeAgo . '</small>
                </div>
                <p class="mb-1">' . nl2br(htmlspecialchars($notification['message'])) . '</p>
                <div class="mt-2">
                    ' . (!$notification['is_read'] ? 
                        '<button class="btn btn-sm btn-outline-primary mark-read-btn" data-id="' . (int)$notification['id'] . '">
                            <i class="fas fa-check me-1"></i>Mark Read
                        </button>' : '') . '
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . (int)$notification['id'] . '">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
              </div>';
    }
    
    echo '</div>';
}
?>