<?php
/**
 * Unified Notification Center
 * Works for both admin/unit director and office head users
 */

session_start();
require_once 'db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Determine user role
$user_role = $_SESSION['role'] ?? '';
$is_admin = in_array($user_role, ['unit_director', 'unit director']);
$is_head = ($user_role === 'head');

if (!$is_admin && !$is_head) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Handle AJAX requests for notification operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $pdo = connect_db();
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $notification_ids = $input['ids'] ?? [];
                if (empty($notification_ids) || !is_array($notification_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
                    exit();
                }
                
                // Sanitize IDs
                $ids = array_map('intval', $notification_ids);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?";
                $params = array_merge($ids, [$_SESSION['user_id']]);
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
                }
                break;
                
            case 'delete':
                $notification_ids = $input['ids'] ?? [];
                if (empty($notification_ids) || !is_array($notification_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
                    exit();
                }
                
                // Sanitize IDs
                $ids = array_map('intval', $notification_ids);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ?";
                $params = array_merge($ids, [$_SESSION['user_id']]);
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Notifications deleted']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete notifications']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Error in notification_center.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
    exit();
}

// For GET requests, serve the notification center page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: #f0f2f5;
            padding: 2rem;
        }
        .notification-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }
        .notification-item {
            border-left: 4px solid #6366f1;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8fafc;
            border-radius: 0 8px 8px 0;
        }
        .notification-item.unread {
            background-color: #fffbeb;
            border-left-color: #f59e0b;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .notification-title {
            font-weight: 600;
            margin: 0;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #6b7280;
        }
        .notification-message {
            margin: 0.5rem 0;
            color: #374151;
        }
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-bell me-2"></i>Notification Center</h1>
            <button id="refreshBtn" class="btn btn-outline-primary">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
        
        <div class="notification-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Your Notifications</h2>
                <div>
                    <button id="markAllReadBtn" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-check-double me-1"></i>Mark All Read
                    </button>
                    <button id="deleteAllBtn" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash-alt me-1"></i>Delete All
                    </button>
                </div>
            </div>
            
            <div id="notificationsContainer">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-2">Loading notifications...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load notifications
            loadNotifications();
            
            // Refresh button
            document.getElementById('refreshBtn').addEventListener('click', loadNotifications);
            
            // Mark all read button
            document.getElementById('markAllReadBtn').addEventListener('click', markAllRead);
            
            // Delete all button
            document.getElementById('deleteAllBtn').addEventListener('click', deleteAll);
        });
        
        // Load notifications
        function loadNotifications() {
            const container = document.getElementById('notificationsContainer');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-2">Loading notifications...</p></div>';
            
            fetch('notifications_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderNotifications(data.notifications);
                    } else {
                        container.innerHTML = '<div class="alert alert-danger">Failed to load notifications</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    container.innerHTML = '<div class="alert alert-danger">Error loading notifications</div>';
                });
        }
        
        // Render notifications
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsContainer');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                        <h5>No notifications</h5>
                        <p class="text-muted">You don't have any notifications at the moment.</p>
                    </div>`;
                return;
            }
            
            let html = '<div class="list-group">';
            
            notifications.forEach(notification => {
                const isUnreadClass = !notification.is_read ? 'unread' : '';
                const unreadIndicator = !notification.is_read ? '<span class="badge bg-warning me-2">NEW</span>' : '';
                
                html += `
                    <div class="notification-item ${isUnreadClass}" data-id="${notification.id}">
                        <div class="notification-header">
                            <h6 class="notification-title">${unreadIndicator}${notification.title}</h6>
                            <small class="notification-time">${notification.time_ago}</small>
                        </div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-actions">
                            ${!notification.is_read ? 
                                `<button class="btn btn-sm btn-outline-primary btn-action mark-read-btn" data-id="${notification.id}">
                                    <i class="fas fa-check me-1"></i>Mark Read
                                </button>` : ''}
                            <button class="btn btn-sm btn-outline-danger btn-action delete-btn" data-id="${notification.id}">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>`;
            });
            
            html += '</div>';
            container.innerHTML = html;
            
            // Add event listeners for mark read buttons
            document.querySelectorAll('.mark-read-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    markAsRead([id]);
                });
            });
            
            // Add event listeners for delete buttons
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    deleteNotifications([id]);
                });
            });
        }
        
        // Mark notifications as read
        function markAsRead(ids) {
            fetch('notification_center.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                } else {
                    alert('Failed to mark notifications as read');
                }
            })
            .catch(error => {
                console.error('Error marking as read:', error);
                alert('Error marking notifications as read');
            });
        }
        
        // Delete notifications
        function deleteNotifications(ids) {
            if (!confirm('Are you sure you want to delete these notifications?')) {
                return;
            }
            
            fetch('notification_center.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                } else {
                    alert('Failed to delete notifications');
                }
            })
            .catch(error => {
                console.error('Error deleting notifications:', error);
                alert('Error deleting notifications');
            });
        }
        
        // Mark all as read
        function markAllRead() {
            fetch('notifications_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Filter unread notifications
                        const unreadIds = data.notifications
                            .filter(n => !n.is_read)
                            .map(n => n.id);
                        
                        if (unreadIds.length > 0) {
                            markAsRead(unreadIds);
                        } else {
                            alert('No unread notifications to mark as read');
                        }
                    }
                });
        }
        
        // Delete all notifications
        function deleteAll() {
            fetch('notifications_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.notifications.length > 0) {
                            const allIds = data.notifications.map(n => n.id);
                            deleteNotifications(allIds);
                        } else {
                            alert('No notifications to delete');
                        }
                    }
                });
        }
    </script>
</body>
</html>