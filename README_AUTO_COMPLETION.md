# Automatic Training Completion System

This system automatically marks trainings as completed when their end date has passed and sends notifications to relevant personnel.

## Features

1. **Automatic Completion**: Trainings are automatically marked as "completed" when their end date passes
2. **Notifications**: 
   - Users receive notifications when their trainings are auto-completed
   - Unit Heads and Office Heads receive notifications when staff complete trainings
   - Unit Heads and Office Heads receive notifications when staff upload proof of completion

## Implementation Details

### Files

1. `auto_complete_trainings.php` - Script that runs daily to check for expired trainings
2. `update_training_status.php` - Modified to send notifications when trainings are manually completed
3. `training_api.php` - Modified to enhance proof upload notifications

### Database Schema

The system uses the existing `training_records` table with these relevant columns:
- `end_date` - Used to determine when a training should be completed
- `status` - Updated from "upcoming" to "completed"
- `proof_uploaded` - Flag indicating if proof has been uploaded

## Setup Instructions

### Setting up the Cron Job

To enable automatic completion, you need to set up a cron job that runs daily:

#### On Linux/Unix systems:

1. Open crontab editor:
   ```bash
   crontab -e
   ```

2. Add this line to run the script daily at 1 AM:
   ```bash
   0 1 * * * /usr/bin/php /path/to/your/project/auto_complete_trainings.php >> /path/to/your/project/auto_complete.log 2>&1
   ```

#### On Windows systems:

1. Open Task Scheduler
2. Create a new task
3. Set it to run daily
4. Set the action to:
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\SDU\auto_complete_trainings.php`
   - Start in: `C:\xampp\htdocs\SDU`

### Testing the System

Several test scripts are provided:

1. `test_auto_complete.php` - Tests the automatic completion feature
2. `test_completion_notification.php` - Tests notifications when trainings are completed
3. `test_proof_upload_notification.php` - Tests notifications when proof is uploaded

Run any test script from the command line:
```bash
php test_auto_complete.php
```

## How It Works

1. **Daily Check**: The cron job runs `auto_complete_trainings.php` daily
2. **Find Expired Trainings**: The script finds all trainings with `status = 'upcoming'` and `end_date < today`
3. **Update Status**: These trainings are updated to `status = 'completed'`
4. **Send Notifications**: 
   - Users receive notifications about their trainings being completed
   - Unit Directors and Office Heads receive notifications about staff completions
5. **Manual Completions**: When users manually mark trainings as completed, notifications are also sent
6. **Proof Uploads**: When users upload proof of completion, notifications are sent to supervisors

## Notification Recipients

- **Unit Directors**: Receive notifications for all staff training completions and proof uploads
- **Office Heads**: Receive notifications for staff in their office who complete trainings or upload proof
- **Staff Members**: Receive notifications when their own trainings are auto-completed

## Customization

You can customize the notification messages by modifying:
- In `auto_complete_trainings.php`: Lines that create notification messages
- In `update_training_status.php`: Notification message templates
- In `training_api.php`: Proof upload notification messages

## Troubleshooting

1. **Cron Job Not Running**: 
   - Check if the cron service is running
   - Verify the path to PHP executable
   - Check permissions on the script

2. **Notifications Not Sent**: 
   - Check database connections
   - Verify user roles in the database
   - Check error logs for exceptions

3. **Trainings Not Auto-completed**: 
   - Run the test script to verify functionality
   - Check database for correct end_date values
   - Verify the cron job is running

## Logs

The system logs important events to:
- PHP error log (standard error logging)
- Custom log entries in `auto_complete.log` (if configured in cron)

Check these logs if you encounter issues with the automatic completion system.