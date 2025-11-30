# SDU Notification System Documentation

## Overview
The SDU Notification System provides a centralized way for administrators and office heads to send and receive notifications within the system. The system supports different types of users with appropriate permissions and notification capabilities.

## Components

### 1. Admin/Unit Director Notifications
Admin users (unit directors) have the ability to:
- Send broadcast notifications to all users, staff only, or office heads only
- View their personal notifications
- Manage their notifications (mark as read, delete)

### 2. Office Head Notifications
Office heads can:
- Send notifications to all staff members in their office
- View their personal notifications
- Manage their notifications (mark as read, delete)

### 3. Staff Notifications
Staff members can:
- Receive notifications from admins and their office head
- View their personal notifications
- Manage their notifications (mark as read, delete)

## API Endpoints

### `/send_notification.php`
Used by admin/unit directors to send broadcast notifications.

**Parameters:**
- `audience` (string): Target audience ('all', 'staff', 'heads')
- `subject` (string, optional): Notification subject
- `message` (string, required): Notification message

### `/head_broadcast.php`
Used by office heads to send notifications to their office staff.

**Parameters:**
- `subject` (string, optional): Notification subject
- `message` (string, required): Notification message

### `/notifications_api.php`
Fetches notifications for the current user in JSON or HTML format.

### `/get_unread_count.php`
Returns the count of unread notifications for the current user.

### `/mark_read.php`
Marks specified notifications as read.

**Parameters (JSON):**
- `ids` (array): Array of notification IDs to mark as read

### `/delete_notifications.php`
Deletes specified notifications.

**Parameters (JSON):**
- `ids` (array): Array of notification IDs to delete

### `/notification_center.php`
Provides a unified notification center interface for both admin and office head users.

## Database Schema

### `notifications` Table
```sql
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Implementation Details

### For Admin Dashboard
The admin dashboard includes:
- A "Send Notification" button that opens a modal for broadcasting messages
- An "Inbox" button that shows all notifications in a modal
- Real-time unread notification count displayed on the inbox button

### For Office Head Dashboard
The office head dashboard includes:
- A "Notify My Staff" button that opens a modal for sending messages to office staff
- A "Notifications" button that shows all personal notifications
- Real-time unread notification count displayed on the notifications button

## Usage Examples

### Sending a Broadcast Notification (Admin)
1. Navigate to the admin dashboard
2. Click "Send Notification"
3. Select the audience (All Users, Staff Only, Office Heads Only)
4. Enter an optional subject
5. Enter the message content
6. Click "Send"

### Sending a Staff Notification (Office Head)
1. Navigate to the office head dashboard
2. Click "Notify My Staff"
3. Enter the message content
4. Click "Send"

### Viewing Notifications
1. Click the "Inbox" (admin) or "Notifications" (office head) button
2. View the list of notifications
3. Mark individual notifications as read or delete them
4. Use "Mark All Read" or "Delete All" for bulk operations

## Testing
A test script (`test_notifications.php`) is included to verify the notification system functionality:
- Sending notifications
- Fetching notifications
- Marking notifications as read
- Deleting notifications

Run the test with: `php test_notifications.php`