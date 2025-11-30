# SDU Training Management System - Notification System

## Overview
This document explains how the notification system works in the SDU Training Management System and how to set up automatic training completion.

## Notification Types

### 1. Training Completion Notifications
When a staff member completes a training (either manually or automatically when the end date passes), notifications are sent to:
- Unit Directors
- Office Heads in the same office

### 2. Proof of Completion Upload Notifications
When a staff member uploads proof of completion for a training, notifications are sent to:
- Unit Directors
- Office Heads in the same office

### 3. Broadcast Notifications
Unit Directors can send broadcast notifications to:
- All users
- Staff only
- Office Heads only

## Automatic Training Completion

### How it works
Trainings are automatically marked as "completed" when their end date passes. This happens in two places:

1. **On Staff Dashboard Load**: When any staff member loads their dashboard, the system checks for any trainings that should be completed and updates them.

2. **Via Cron Job**: A separate script can be run periodically to check and update all trainings across the system.

### Setting up the Cron Job
To ensure trainings are automatically completed even when no staff members are actively using the system, set up a cron job to run the auto-complete script daily.

#### For Linux/Unix systems:
```bash
# Add this line to your crontab (crontab -e)
0 0 * * * /usr/bin/php /path/to/your/sdu/auto_complete_trainings.php
```

This will run the script daily at midnight.

#### For Windows systems:
Use Windows Task Scheduler to run the script daily:
1. Open Task Scheduler
2. Create a new task
3. Set trigger to daily
4. Set action to run: `php.exe` with arguments: `C:\path\to\your\sdu\auto_complete_trainings.php`

## Notification API Endpoints

### For Admins (Unit Directors)
- `admin_api.php?action=get_notifications` - Get notifications for the current admin
- `admin_api.php?action=mark_notification_read` - Mark a single notification as read
- `admin_api.php?action=mark_notifications_read` - Mark multiple notifications as read
- `admin_api.php?action=delete_notification` - Delete a single notification
- `admin_api.php?action=delete_notifications` - Delete multiple notifications

### For Staff
- `notifications_api.php` - Get notifications for the current staff member
- `mark_read.php` - Mark notifications as read
- `delete_notifications.php` - Delete notifications

## Training Status Logic

The system automatically determines training status based on dates:
- **Completed**: End date is before current date
- **Ongoing**: Current date is between start and end dates (inclusive)
- **Upcoming**: End date is after current date

## File Upload Notifications

When a staff member uploads proof of completion:
1. The file is saved to the server
2. A record is created in the `training_proofs` table
3. The `proof_uploaded` flag is set to 1 in the `training_records` table
4. Notifications are sent to Unit Directors and Office Heads

## Customization

To modify the notification recipients or messages:
1. Edit `training_api.php` in the `upload_proof` section
2. Edit `update_training_status.php` in the completion notification section
3. Edit `auto_complete_trainings.php` for automatic completion notifications

## Troubleshooting

If notifications are not being sent:
1. Check that the database connection is working
2. Verify that Unit Directors and Office Heads have the correct roles in the database
3. Check the PHP error logs for any issues
4. Ensure the notification cron job is running (if applicable)